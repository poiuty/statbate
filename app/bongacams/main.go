package main

import (
	_ "github.com/ClickHouse/clickhouse-go"
	_ "github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
	jsoniter "github.com/json-iterator/go"
	"log"
	"net"
	"net/http"
	"os"
)

type Rooms struct {
	Count chan int
	Json  chan string
	Add   chan Info
	Del   chan string
}

var hub = newHub()
var Mysql, Clickhouse *sqlx.DB
var json = jsoniter.ConfigCompatibleWithStandardLibrary

var save = make(chan saveData, 100)
var slog = make(chan saveLog, 100)

var rooms = &Rooms{
	Count: make(chan int),
	Json:  make(chan string),
	Add:   make(chan Info),
	Del:   make(chan string),
}

func main() {
	initMysql()
	initClickhouse()

	go hub.run()
	go mapRooms()
	go announceCount()
	go saveDB()
	go saveLogs()

	http.HandleFunc("/bongacams/ws/", hub.wsHandler)
	http.HandleFunc("/bongacams/cmd/", cmdHandler)
	http.HandleFunc("/bongacams/list/", listHandler)
	http.HandleFunc("/bongacams/debug/", debugHandler)

	const SOCK = "/tmp/bongacams.sock"
	os.Remove(SOCK)
	unixListener, err := net.Listen("unix", SOCK)
	if err != nil {
		log.Fatal("Listen (UNIX socket): ", err)
	}
	defer unixListener.Close()
	os.Chmod(SOCK, 0777)
	log.Fatal(http.Serve(unixListener, nil))
}

func initMysql() {
	db, err := sqlx.Connect("mysql", "u:p@unix(/var/run/mysqld/mysqld.sock)/bongacams?interpolateParams=true")
	if err != nil {
		panic(err)
	}
	Mysql = db
}

func initClickhouse() {
	db, err := sqlx.Connect("clickhouse", "tcp://127.0.0.1:9000/bongacams?compress=true&debug=false")
	if err != nil {
		panic(err)
	}
	Clickhouse = db
}