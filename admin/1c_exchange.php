<?PHP
class Exchange1c {
 
        private $mode;
        private $filename;
 
 
        public function __construct() {
                // принимаем значение mode
                $this->mode = $_GET['mode'];
                $this->filename = $_GET['filename'];
        }
 
        public function run(){
                $mode = $this->mode;
                // и здесь, в зависимости, что отправла 1С
                // вызываем одноименный метод
                /*
                 * 1. checkauth
                 * 2. init
                 * 3. file
                 * 4.1 import - [filename] => import.xml
                 * 4.2 import - [filename] => offers.xml
                 */
                $this->$mode();
        }
 
 
        /*
         * Этап 1. Авторизовываем 1с клиента
         */
        public function checkauth() {
                echo "success\n";
                echo session_name()."\n";
                echo session_id()."\n";
                exit;
        }
 
        /*
         * Этап 2. Говрим 1с, умеем или не умеем работать с архивами
         * в нашем случае - умеем :)
         */
        public function init() {
                $zip = extension_loaded('zip') ? 'yes' : 'no';
                echo 'zip='.$zip."\n";
                echo "file_limit=0\n";
                exit;
        }
 
        /*
         * Этап 3. Принимаем файл и распаковываем его
         */
        public function file() {
 
                // вытаскиваем сырые данные
                $data = file_get_contents('php://input');
 
                //Сохраняем файл импорта в zip архиве
                file_put_contents($this->filename, $data);
               
                // распаковываем
                if(file_exists($this->filename)) {
                        // работаем с zip
                        $zip = new ZipArchive;
                        //все в порядке с архивом?
                        if($res = $zip->open($this->filename, ZIPARCHIVE::CREATE)) {
 
                                // распаковываем два файла в формате xml куда-то
                                // в нашем случае в этот же каталог
                                $zip->extractTo(__DIR__);
                                $zip->close();
 
                                // удаляем временный файл
                                unlink($this->filename);
                                //Всё получилось?
                                echo "success\n";
                                exit;
                        }
                }
                // если ничего не получилось
                echo "failure\n";
                exit;
        }
 
        /*
         * Этап 3 и 4 работаем с файлами обмена
         */
        public function import() {
                // используем читалку xml
                $xml = simplexml_load_file($this->filename);
                if($xml && $this->filename == 'import.xml') {
                        /// обрабатываете import.xml как простой xml
                        //Считываете данные, подключаетесь к своей БД, записываете и т.д
                        //во время работы скрипта можете отправлять progress
                        // если все успешно закончилось отправляете успех
                   echo "success\n";
                        echo session_name()."\n";
                        echo session_id()."\n";
                        exit;
 
                }elseif($xml && $this->filename == 'offers.xml') {
                        // обрабатываете offers.xml как простой xml
                        //Считываете данные, подключаетесь к своей БД, записываете и т.д
                        //во время работы скрипта можете отправлять progress
                        // если все успешно закончилось отправляете успех
                        echo "success\n";
                        echo session_name()."\n";
                        echo session_id()."\n";
                        exit;
                }else{
                        echo "Ошибка загрузки XML\n";
                        foreach (libxml_get_errors() as $error) {
                                echo "\t", $error->message;
                        }
                        exit;
                }
        }
 
}
session_start();
$exaple = new Exchange1c();
$exaple->run();
 