<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailInfoOrderDelivered;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class eventsmanager_notify_orderdeliver extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__ORDER_DELIVER__');

    /**
     *
     * @return notify_orderdeliver
     */
    public function __construct(Application $app, eventsmanager_broker $broker)
    {
        $this->group = _('Commande');
        parent::__construct($app, $broker);

        return $this;
    }

    /**
     *
     * @return string
     */
    public function icon_url()
    {
        return '/skins/prod/000000/images/disktt_history.gif';
    }

    /**
     *
     * @param  string  $event
     * @param  Array   $params
     * @param  Array   $object
     * @return boolean
     */
    public function fire($event, $params, &$object)
    {
        $default = array(
            'from'    => ''
            , 'to'      => ''
            , 'ssel_id' => ''
            , 'n'       => ''
        );

        $params = array_merge($default, $params);

        $dom_xml = new DOMDocument('1.0', 'UTF-8');

        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;

        $root = $dom_xml->createElement('datas');

        $from = $dom_xml->createElement('from');
        $to = $dom_xml->createElement('to');
        $ssel_id = $dom_xml->createElement('ssel_id');
        $n = $dom_xml->createElement('n');

        $from->appendChild($dom_xml->createTextNode($params['from']));
        $to->appendChild($dom_xml->createTextNode($params['to']));
        $ssel_id->appendChild($dom_xml->createTextNode($params['ssel_id']));
        $n->appendChild($dom_xml->createTextNode($params['n']));

        $root->appendChild($from);
        $root->appendChild($to);
        $root->appendChild($ssel_id);
        $root->appendChild($n);

        $dom_xml->appendChild($root);

        $datas = $dom_xml->saveXml();

        $mailed = false;

        if ($this->shouldSendNotificationFor($params['to'])) {
            $readyToSend = false;
            try {
                $user_from = User_Adapter::getInstance($params['from'], $this->app);
                $user_to = User_Adapter::getInstance($params['to'], $this->app);

                $receiver = Receiver::fromUser($user_to);
                $emitter = Emitter::fromUser($user_from);

                $repository = $this->app['EM']->getRepository('\Entities\Basket');
                $basket = $repository->find($params['ssel_id']);

                $readyToSend = true;
            } catch (\Exception $e) {

            }

            if ($readyToSend) {
                $url = $this->app->url('lightbox_compare', array(
                    'ssel_id' => $basket->getId(),
                    'LOG' => $this->app['tokens']->getUrlToken(
                        \random::TYPE_VIEW,
                        $user_to->get_id(),
                        null,
                        $basket->getId()
                    )
                ));

                $mail = MailInfoOrderDelivered::create($this->app, $receiver, $emitter, null);
                $mail->setButtonUrl($url);
                $mail->setBasket($basket);
                $mail->setDeliverer($user_from);

                $this->app['notification.deliverer']->deliver($mail);
                $mailed = true;
            }
        }

        return $this->broker->notify($params['to'], __CLASS__, $datas, $mailed);
    }

    /**
     *
     * @param  Array   $datas
     * @param  boolean $unread
     * @return string
     */
    public function datas($datas, $unread)
    {
        $sx = simplexml_load_string($datas);

        $from = (string) $sx->from;
        $ssel_id = (string) $sx->ssel_id;
        $n = (int) $sx->n;

        try {
            $registered_user = User_Adapter::getInstance($from, $this->app);
        } catch (Exception $e) {
            return array();
        }

        $sender = User_Adapter::getInstance($from, $this->app)->get_display_name();

        try {
            $repository = $this->app['EM']->getRepository('\Entities\Basket');

            $basket = $repository->findUserBasket($this->app, $ssel_id, $this->app['authentication']->getUser(), false);
        } catch (Exception $e) {
            return array();
        }
        $ret = array(
            'text'  => sprintf(
                _('%1$s vous a delivre %2$d document(s) pour votre commande %3$s'), $sender, $n, '<a href="/lightbox/compare/'
                . (string) $sx->ssel_id . '/" target="_blank">'
                . $basket->getName() . '</a>'
            )
            , 'class' => ''
        );

        return $ret;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return _('Reception de commande');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return _('Reception d\'une commande');
    }

    /**
     * @param integer $usr_id The id of the user to check
     *
     * @return boolean
     */
    public function is_available($usr_id)
    {
        return true;
    }
}
