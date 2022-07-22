package main

import (
	"fmt"
	"time"
)

type saveData struct {
	Room   string
	From   string
	Rid    int64
	Amount int64
	Now    int64
}

type saveLog struct {
	Rid int64
	Now int64
	Mes string
}

type DonatorCache struct {
	Id   int64
	Last int64
}

type AnnounceIndex struct {
	Index int64 `json:"index"`
}

func getDonId(name string) int64 {
	var id int64
	err := Mysql.Get(&id, "SELECT id FROM donator WHERE name=?", name)
	if err != nil {
		res, _ := Mysql.Exec("INSERT INTO donator (`name`) VALUES (?)", name)
		id, _ = res.LastInsertId()
	}
	return id
}

func getRoomInfo(name string) (int64, bool) {
	var id int64
	result := true
	err := Mysql.Get(&id, "SELECT id FROM room WHERE name=?", name)
	if err != nil {
		result = false
	}
	return id, result
}

func getSumTokens() int64 {
	r := struct {
		Date string
		Sum  int64
	}{}
	err := Clickhouse.Get(&r, "SELECT toStartOfHour(toDateTime(`unix`)) as date, SUM(`token`) as sum FROM `stat` WHERE time = today() GROUP BY date ORDER BY date DESC LIMIT 1")
	if err == nil && r.Sum > 0 {
		return r.Sum
	}
	return 0
}

func saveDB() {
	last := time.Now().Unix()
	hours, _, _ := time.Now().Clock()

	bulk := make(map[int]saveData)
	data := make(map[string]*DonatorCache)
	index := make(map[string]int64)

	index = map[string]int64{"hours": int64(hours), "tokens": getSumTokens(), "last": last}

	for {
		select {
		case m := <-save:
			//fmt.Println("Save channel:", len(save), cap(save))

			now := time.Now().Unix()

			if _, ok := data[m.From]; ok {
				data[m.From].Last = now
			} else {
				data[m.From] = &DonatorCache{Id: getDonId(m.From), Last: now}
			}

			Mysql.Exec("UPDATE `room` SET `last` = ? WHERE `id` = ?", m.Now, m.Rid)

			num := len(bulk)

			bulk[num] = m

			if num >= 999 || now >= last+10 {
				tx, err := Mysql.Begin()
				if err == nil {
					for _, v := range bulk {
						tx.Exec("INSERT INTO `stat` (`did`, `rid`, `token`, `time`) VALUES (?, ?, ?, ?)", data[v.From].Id, v.Rid, v.Amount, v.Now)
					}
				}
				tx.Commit()

				tx, err = Clickhouse.Begin()
				if err == nil {
					st, _ := tx.Prepare("INSERT INTO stat VALUES (?, ?, ?, ?, ?)")
					//fmt.Println("G:", err)
					for _, v := range bulk {
						st.Exec(uint32(data[v.From].Id), uint32(v.Rid), uint32(v.Amount), time.Unix(v.Now, 0), uint32(v.Now))
						//fmt.Println("B:", aaa, sss)
					}
					tx.Commit()
					st.Close()
				}

				last = now
				bulk = make(map[int]saveData)
			}
			if m.Amount > 99 {
				msg, err := json.Marshal(AnnounceDonate{Room: m.Room, Donator: m.From, Amount: m.Amount})
				if err == nil {
					go broadcast(msg)
				}
			}

			hours, minutes, seconds := time.Now().Clock()
			if int64(hours) == index["hours"] {
				index["tokens"] += m.Amount
			} else {
				index = map[string]int64{"hours": int64(hours), "tokens": 0, "last": 0}
			}
			if minutes >= 5 && now > index["last"]+30 {
				seconds += minutes * 60
				msg, err := json.Marshal(AnnounceIndex{Index: index["tokens"] / int64(seconds) * 3600 / 1000 * 5 / 100})
				if err == nil {
					go broadcast(msg)
				}
				index["last"] = now
			}

			if randInt(0, 10000) == 777 { // 0.001%
				l := len(data)
				for k, v := range data {
					if now > v.Last+60*60*48 {
						delete(data, k)
					}
				}
				fmt.Println("Clean map:", l, "=>", len(data))
			}
		}
	}
}

func saveLogs() {
	last := time.Now().Unix()
	bulk := make(map[int]saveLog)
	for {
		select {
		case m := <-slog:
			if len(m.Mes) > 0 {
				num := len(bulk)
				bulk[num] = m
				now := time.Now().Unix()
				if num >= 2047 || now >= last+10 {
					tx, err := Mysql.Begin()
					if err == nil {
						for _, v := range bulk {
							tx.Exec("INSERT INTO `logs` (`rid`, `time`, `mes`) VALUES (?, ?, ?)", v.Rid, v.Now, v.Mes)
						}
						tx.Commit()
					}
					last = now
					bulk = make(map[int]saveLog)
				}
			}
		}
	}
}
