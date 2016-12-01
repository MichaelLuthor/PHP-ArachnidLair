<?php
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
abstract class AbstractSpirder {
    /** @var array */
    private $tasks = array();
    /** @var Client */
    public $client = null;
    /** @var CookieJar */
    public $cookieJar = null;
    
    /** @return void */
    protected function init() {
        $this->client = new Client();
        $this->cookieJar = new CookieJar();
    }
    
    /**
     * @param unknown $task
     * @param array $option
     */
    public function addTask(  $task, $option=array() ) {
        $this->tasks[] = array('url'=>$task, 'option'=>$option);
    }
    
    /** @return void */
    protected function generateTasks() {}
    
    /** @return void */
    protected function onTaskFinished($task, $response) {}
    
    /** @return boolean */
    protected function beforeTaskStarted(&$task) {}
    
    /** @return void */
    public function foraging() {
        $this->init();
        $this->startTask();
    }
    
    /** @return void */
    protected function startTask() {
        do {
            foreach ( $this->tasks as $index => $task ) {
                $this->beforeTaskStarted($task);
                $response = $this->client->get($task['url']->toString(), array(
                    'cookies'=>$this->cookieJar,
                    'verify' => false,
                ));
                $this->onTaskFinished($task, $response);
                unset($this->tasks[$index]);
            }
            $this->generateTasks();
            if ( empty($this->tasks) ) {
                $this->say("All Task Finished.");
                break;
            }
        } while (true);
    }
    
    /** @param string $message */
    public function say( $message ) {
        $message = call_user_func_array('sprintf', func_get_args());
        echo $message."\n";
    }
    
    /**
     * @param unknown $seconds
     */
    public function timeCounter( $seconds ) {
        while ( $seconds > 0 ) {
            $this->say("Countdown Timer : %d", $seconds);
            $seconds --;
            sleep(1);
        }
    }
}