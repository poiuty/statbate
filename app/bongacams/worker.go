package main

import (
	"fmt"
	"github.com/gorilla/websocket"
	jsoniter "github.com/json-iterator/go"
	"net/http"
	"net/url"
	"time"
	"strings"
	//"bytes"
)

var uptime = time.Now().Unix()

type AuthResponse struct {
	Status    string `json:"status"`
	LocalData struct {
		DataKey string `json:"dataKey"`
	} `json:"localData"`
	UserData struct {
		Username    string `json:"username"`
		DisplayName string `json:"displayName"`
		Location    string `json:"location"`
		Chathost    string `json:"chathost"`
		IsRu        bool   `json:"isRu"`
	} `json:"userData"`
}

type ServerResponse struct {
	TS   int64           `json:"ts"`
	Type string          `json:"type"`
	Body jsoniter.RawMessage `json:"body"`
}

type DonateResponse struct {
	F struct {
		Username string `json:"username"`
	} `json:"f"`
	A int64 `json:"a"`
}

type AnnounceCount struct {
	Count int `json:"count"`
}

type AnnounceDonate struct {
	Room    string `json:"room"`
	Donator string `json:"donator"`
	Amount  int64  `json:"amount"`
}

func mapRooms() {

	data := make(map[string]*Info)

	for {
		select {
		case m := <-rooms.Add:
			data[m.room] = &Info{Server: m.Server, Proxy: m.Proxy, Start: m.Start, Last: m.Last, Income: m.Income}

		case s := <-rooms.Json:
			j, err := json.Marshal(data)
			if err == nil {
				s = string(j)
			}
			rooms.Json <- s

		case <-rooms.Count:
			rooms.Count <- len(data)

		case key := <-rooms.Del:
			delete(data, key)
			removeRoom(key)
		}
	}
}

func announceCount() {
	for {
		time.Sleep(30 * time.Second)
		rooms.Count <- 0
		l := <-rooms.Count
		msg, err := json.Marshal(AnnounceCount{Count: l})
		if err == nil {
			hub.broadcast <- msg
		}
	}
}

func getAMF(room string) (bool, *AuthResponse) {
	
	v := &AuthResponse{}
	
	req, err := http.NewRequest(http.MethodPost, "https://rt.bongocams.com/tools/amf.php?res=771840&t=1654437233142", strings.NewReader(`method=getRoomData&args[]=`+room))
	if err != nil {
		fmt.Println(err.Error())
		return false, v
	}
	req.Header.Add("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8")
	req.Header.Add("X-Requested-With", "XMLHttpRequest")
	req.Header.Add("Accept", "application/json")
	req.Header.Add("Referrer", "https://bongacams.com")
	req.Header.Add("User-agent", "curl/7.79.1")

	rsp, err := http.DefaultClient.Do(req)
	if err != nil {
		fmt.Println(err.Error())
		return false, v
	}
	defer rsp.Body.Close()

	if err = json.NewDecoder(rsp.Body).Decode(v); err != nil {
		fmt.Println(err.Error())
		return false, v
	}
	
	return true, v
}

func statRoom(chQuit chan struct{}, room, server, proxy string, info *tID, u url.URL) {
	//fmt.Println("Start", room, "server", server, "proxy", proxy)

	ok, v := getAMF(room)
	if !ok {
		fmt.Println("exit: no amf parms")
		return
	}	
	
	Dialer := *websocket.DefaultDialer

	proxyMap := make(map[string]string)
	proxyMap["us"] = "ip:port"
	proxyMap["fi"] = "ip:port"

	if _, ok := proxyMap[proxy]; ok {
		Dialer = websocket.Dialer{
			Proxy: http.ProxyURL(&url.URL{
				Scheme: "http", // or "https" depending on your proxy
				Host:   proxyMap[proxy],
				Path:   "/",
			}),
			HandshakeTimeout: 45 * time.Second, // https://pkg.go.dev/github.com/gorilla/websocket
		}
	}
	
	c, _, err := Dialer.Dial(u.String(), nil)
	if err != nil {
		fmt.Println(err.Error())
		return
	}

	
	now := time.Now().Unix()
	workerData := Info{room, server, proxy, now, now, 0}	
	
	if err = c.WriteMessage(websocket.TextMessage, []byte(fmt.Sprintf(`{"id":%d,"name":"joinRoom","args":["%s",{"username":"%s","displayName":"%s","location":"%s","chathost":"%s","isRu":%t,"isPerformer":false,"hasStream":false,"isLogged":false,"isPayable":false,"showType":"public"},"%s"]}`, 1, v.UserData.Chathost, v.UserData.Username, v.UserData.DisplayName, v.UserData.Location, v.UserData.Chathost, v.UserData.IsRu, v.LocalData.DataKey))); err != nil {
		fmt.Println(err.Error())
		return
	}
	
	_, message, err := c.ReadMessage()
	if err != nil {
		fmt.Println(err.Error())
		return
	}
	
	slog <- saveLog{info.Id, now, string(message)}
	
	if err = c.WriteMessage(websocket.TextMessage, []byte(fmt.Sprintf(`{"id":%d,"name":"ChatModule.connect","args":["public-chat"]}`, 2))); err != nil {
		fmt.Println(err.Error())
		return
	}
	
	_, message, err = c.ReadMessage()
	if err != nil {
		fmt.Println(err.Error())
		return
	}
	
	slog <- saveLog{info.Id, now, string(message)}
	quit := make(chan bool)
	pid := 3
	
	go func (){
		ticker := time.NewTicker(30 * time.Second)
		defer ticker.Stop()
		for {
			select {
				case <- quit:
					return
				case <-ticker.C:
					if err = c.WriteMessage(websocket.TextMessage, []byte(fmt.Sprintf(`{"id":%d,"name":"ping"}`, pid))); err != nil {
						fmt.Println(err.Error())
						close(chWorker.Map[room].chQuit)
						return
					}
					//fmt.Println("ping", room)
					pid++
				break
			}
		}
	}()
	
	defer c.Close()
	for {
		select {
		case <-chQuit:
			fmt.Println("Exit room:", room)
			rooms.Del <- room
			quit <- true
			return

		default:	
			_, message, err := c.ReadMessage()
			if err != nil {
				fmt.Println(err.Error())
				rooms.Del <- room
				quit <- true
				return
			}
			
			now = time.Now().Unix()
			
			workerData.Last = now
			rooms.Add <- workerData
			
			slog <- saveLog{info.Id, now, string(message)}

			m := &ServerResponse{}

			if err = json.Unmarshal(message, m); err != nil {
				fmt.Println(err.Error())
				continue
			}
			
			//if m.Type == "ServerMessageEvent:PERFORMER_STATUS_CHANGE" && bytes.Contains(m.Body, []byte(`offile`)) {
			if m.Type == "ServerMessageEvent:PERFORMER_STATUS_CHANGE" && string(m.Body) == `"offline"` {
				fmt.Println(m.Type, room)
				rooms.Del <- room
				quit <- true
				return
			}
			
			if m.Type == "ServerMessageEvent:ROOM_CLOSE" {
				fmt.Println(m.Type, room)
				rooms.Del <- room
				quit <- true
				return
			}
			
			if m.Type == "ServerMessageEvent:INCOMING_TIP" {
				d := &DonateResponse{}
				if err = json.Unmarshal(m.Body, d); err == nil {
					//fmt.Println(d.F.Username, " send ", d.A, "tokens")
					
					save <- saveData{room, d.F.Username, info.Id, d.A, now}

					workerData.Income += d.A
					rooms.Add <- workerData
				}
			}
		}
	}
}