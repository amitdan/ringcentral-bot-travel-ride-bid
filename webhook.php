<?php

require('vendor/autoload.php');
require('GlipBotman.php');


use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\DriverManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GlipDriver\GlipBotman;

// Parse the .env file
$dotenv = new Dotenv\Dotenv(getcwd());
$dotenv->load();


// Load the values from .env
$config = [
    'GLIP_SERVER' => $_ENV['GLIP_SERVER'],
    'GLIP_APPKEY' => $_ENV['GLIP_APPKEY'],
    'GLIP_APPSECRET' => $_ENV['GLIP_APPSECRET'],
    'GLIP_SANDBOX_NUMBER' => $_ENV['GLIP_SANDBOX_NUMBER'],
    'GLIP_BOT_NAME' => $_ENV['GLIP_BOT_NAME']
];


/*
 * Create the Subscription using Webhooks Method
 */
$cacheDir = __DIR__ . DIRECTORY_SEPARATOR . '_subscribe';
if (!file_exists($cacheDir)) {

    mkdir($cacheDir);
    $request = Request::createFromGlobals();
    // GlipWebhook verification
    if ($request->headers->has('Validation-Token'))
    {

        return Response::create('',200,array('Validation-Token' => getallheaders()['Validation-Token']))->send();
    }
}

// Load the Driver into Botman
DriverManager::loadDriver(GlipBotman::class);


// Create a Botman Instance
$botman = BotManFactory::create($config);

$botman->hears('Want to bid a car ride', function(BotMan $bot) {
    $bot->startConversation(new OnboardingConversation);
})->driver(GlipBotman::class);

class OnboardingConversation extends Conversation
{
    protected $firstname;

    protected $email;
	
	protected $city;
	
	protected $month;
	
	protected $bidAmount;

    public function askFirstname()
    {
        $this->ask('Hello! What is your firstname?', function(Answer $answer) {
            // Save result
            $this->firstname = $answer->getText();

            $this->say('Nice to meet you '.$this->firstname);
            $this->askEmail();
        });
    }

    public function askEmail()
    {
        $this->ask('One more thing - what is your email?', function(Answer $answer) {
            // Save result
            $this->email = $answer->getText();

            $this->say('Great - that is all we need, '.$this->firstname);
			$this->askCity();
        });
    }
	
	public function askCity()
    {
        $this->ask('Which city you want to bid a car ride?', function(Answer $answer) {
            // Save result
            $this->city = $answer->getText();

            $this->say('Great !!, '.$this->firstname);
			$this->askMonth();
        });
    }
	
	public function askMonth()
    {
        $this->ask('Which month ?', function(Answer $answer) {
            // Save result
            $this->month = $answer->getText();
            
			$this->askAmount();
        });
    }
	
	public function askAmount()
    {
        $this->ask('Please specify bid amount in rupees?', function(Answer $answer) {
            // Save result
            $this->bidAmount = $answer->getText();

            $this->say('Great - that is all we need, '.$this->firstname);
			$this->say('Thanks for your entry, we have sent an SMS with your bid details to your mobile number. We will also notify if your entry got selected');			
        });
    }

    public function run()
    {
        // This will be called immediately
        $this->askCity();
    }
}

$botman->hears('send sms to {number}', function (BotMan $bot, $query) {
    $bot->reply($query);
})->driver(GlipBotman::class);

// Start listening
$botman->listen();