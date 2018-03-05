<?php

require './vendor/autoload.php';

use Baidu\Duer\Botsdk\Card\ListCard;
use Baidu\Duer\Botsdk\Card\ListCardItem;

class Bot extends \Baidu\Duer\Botsdk\Bot
{

    static $options = ['剪刀' => 'scissors', '布' => 'paper', '石头' => 'rock', '蜥蜴' => 'lizard', '史派克' => 'Spock'];
    static $zhName = '石头-剪刀-布-蜥蜴-史波克';
    static $enName = 'rock-paper-scissors-lizard-Spock';

    static $rules = array(
        'scissors cuts paper.' => '剪刀剪布',
        'scissors decapitates lizard.' => '剪刀将蜥蜴斩首.',

        'lizard poisons Spock.' => '蜥蜴毒死史波克.',
        'lizard eats paper.' => '蜥蜴吃掉纸',

        'paper disproves Spock.' => '论文证明史波克不存在.',
        'paper covers rock.' => '布包石头.',

        'Spock vaporizes rock.' => '史波克把石头融化.',
        'Spock smashes scissors.' => '史波克踩碎剪刀',

        'rock crushes scissors.' => '石头坏剪刀.',
        'rock crushes lizard.' => '石头砸死蜥蜴.',
    );

    /**
     * @param null
     * @return null
     * */
    public function __construct($postData = [])
    {
        //parent::__construct($postData, file_get_contents(dirname(__file__).'/../../src/privkey.pem'));
        //parent::__construct(file_get_contents(dirname(__file__).'/../../src/privkey.pem'));
        parent::__construct();
//
        $this->redis = new Predis\Client([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
        ]);


        $this->log = new \Baidu\Duer\Botsdk\Log([
            // 日志存储路径
            'path' => './log/',
            // 日志打印最低输出级别
            'level' => \Baidu\Duer\Botsdk\Log::NOTICE,
        ]);

        // 记录这次请求的query
        $this->log->setField('query', $this->request->getQuery());
        //$this->addIntercept(new \Baidu\Duer\Botsdk\Plugins\DuerSessionIntercept());
        $this->addLaunchHandler(function () {
            $card = new ListCard();
            $item = new ListCardItem();
            $item->setTitle(self::$zhName)
                ->setContent(self::$enName)
                ->setUrl('http://captcha.mojotv.cn')
                ->setImage('http://img.trytv.org/bangbangbang.jpg');
            //http://img.trytv.org/bozinga.jpg
            $card->addItem($item);
            $card->addCueWords(['石头', '剪刀', '布', '蜥蜴', '史波克']);

            $this->waitAnswer();
            return [
                'card' => $card,
                'outputSpeech' => '欢迎来和谢耳朵一起玩耍啊!'
            ];

        });

        $this->addSessionEndedHandler(function () {
            $card = new \Baidu\Duer\Botsdk\Card\ImageCard();
            $card->addItem('http://img.trytv.org/bozinga.jpg');
            return [
                'card' => $card,
                //'outputSpeech' => '<speak>欢迎光临</speak>'
                'outputSpeech' => self::$zhName . self::$enName . '欢迎下次来玩啊!',
            ];
        });

        // 在匹配到intent的情况下,首先询问月薪
        $this->addIntentHandler('game', function () {
            $card = new \Baidu\Duer\Botsdk\Card\ImageCard();
            $card->addItem('http://www.ryedu.net/syy/Uploads_20/201109/2011090611460630.jpg');

            $yourOp = $this->getSlot('op');
            $botOp = array_rand(self::$options, 1);
            $yourEnop = self::$options[$yourOp];
            $botEnop = self::$options[$botOp];
            $this->redis->lpush('ssss', $yourEnop);
            $uid = $this->request->getUserId();
            $this->redis->lpush('playerIds', $uid);
            $this->redis->lpush('requests', json_encode($this->request->getData()));

            foreach (self::$rules as $en => $zh) {
                $yV = strrpos($en, $yourEnop);
                $bV = strrpos($en, $botEnop);
                if ($yV !== false && $bV !== false) {
                    if ($yV > $bV) {
                        $res = '非常遗憾您输了!';
                        $speakTxt = "谢耳朵出的是:{$botOp},而您出的是:{$yourOp}.$res $zh $en";
                        $this->redis->hincrby('lose', $uid, 1);

                    } else if ($yV === $bV) {
                        $res = '英雄所见略同! Great minds think alike!';
                        $speakTxt = "谢耳朵出的是:{$botOp},而您出的是:{$yourOp}.$res";
                        $this->redis->hincrby('tie', $uid, 1);

                    } else {
                        $res = '恭喜您,您赢了!';
                        $speakTxt = "谢耳朵出的是:{$botOp},而您出的是:{$yourOp}.$res $zh $en";
                        $this->redis->hincrby('win', $uid, 1);

                    }
                    return [
                        'card' => $card,
                        'outputSpeech' => $speakTxt,
                    ];
                }
            }
        });
    }

}
