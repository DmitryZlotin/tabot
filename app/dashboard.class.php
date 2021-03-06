<?php

    class dashboard {

        private $log;
        private $connection;

        function __construct($log) {
            $this->log = $log;

            //Define DB
            $userName = $_ENV['DB_USERNAME']?$_ENV['DB_USERNAME']:config::$db['userName'];
            $password = $_ENV['DB_PASSWORD']?$_ENV['DB_PASSWORD']:config::$db['password'];
            $server = $_ENV['DB_SERVER']?$_ENV['DB_SERVER']:config::$db['server'];
            $name = $_ENV['DB_NAME']?$_ENV['DB_NAME']:config::$db['name'];

            $this->connection = new PDO('mysql:host='.$server.';dbname='.$name, $userName, $password);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);  // allows us to catch errors
        }

        private function getBasicKPIs() {
             try {
                $query = $this->connection->prepare(
                        'SELECT * FROM
                            (SELECT COUNT(*) AS messages FROM messages) t1
                        INNER JOIN
                            (SELECT COUNT(distinct userId) AS users FROM  messages) t2');

                if(!$query) {
                    $this->log->error('PDO error', $this->connection->errorInfo());
                    die();
                } else {
                    $res = $query->execute();
                    $data = $query->fetch();

                    return ['messages'=>$data['messages'],'users'=>$data['users']];
                }
            } catch (PDOException $e) {
                $this->log->error('PDO error', $e->getMessage());
                die();
            }
        }

        private function getUsersActivity() {

            try {
                $query = $this->connection->prepare('SELECT count(*) as messages, concat(day(created),\'/\',month(created)) as created FROM `messages` group by created limit 6');

                if(!$query) {
                    $this->log->error('PDO error', $this->connection->errorInfo());
                    die();
                } else {
                    $res = $query->execute();
                    $data = $query->fetchAll();

                    return $data;
                }
            } catch (PDOException $e) {
                $this->log->error('PDO error', $e->getMessage());
                die();
            }
        }

        public function getKPIs() {

            $basicKPIs = $this->getBasicKPIs();
            $activity = $this->getUsersActivity();

            return [
                'messages' => $basicKPIs['messages'],
                'users' => $basicKPIs['users'],
                'activity' => $activity
            ];

        }

        public function getUsers() {

            try {
                $query = $this->connection->prepare('SELECT DISTINCT userId FROM messages');

                if(!$query) {
                    $this->log->error('PDO error', $this->connection->errorInfo());
                    die();
                } else {
                    $res = $query->execute();
                    $data = $query->fetchAll();

                    return $data;
                }
            } catch (PDOException $e) {
                $this->log->error('PDO error', $e->getMessage());
                die();
            }

        }

        public function sendMessage($messageText, $userId) {

            $botServer = new BotServer($this->log, config::$bot['verificationToken']);
            $botServer->sendMessage(new message($messageText, new user($userId)));
            return true;

        }

    }

?>
