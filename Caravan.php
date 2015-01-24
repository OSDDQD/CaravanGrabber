<?php
namespace Core\GrabberBundle\Caravan;

use PhpQuery\PhpQuery as phpQuery;
use Yandex\Translate\Translator;
use Yandex\Translate\Exception;
use Core\GenericBundle\Basic\Foundation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


abstract class Caravan implements CaravanInterface
{
    protected $_curl;
    protected $_curlContent;
    protected $_loot = array();
    const YandexApiKey = '';
    const PosterName = 'autobot';

    protected function curlInit($url)
    {
        $headers = array(
            'GET ' . $url . ' HTTP/1.1',
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0',
            'Accept: text/html',
            'Accept-Language: ru,en-us;',
            'Accept-Charset: windows-1251,utf-8;',
            'Connection: close'
        );
        $this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_URL, $url);
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, TRUE);
    }

    protected  function curlClose()
    {
        curl_close($this->_curl);
    }

    public function setData()
    {
        $sc = new Controller;
        $em = $sc->getDoctrine()->getManager();
        $autobotUser = $em->getRepository('CoreAuthBundle:User')->findOneBy(array('username' => self::PosterName));


        foreach ($this->getMap() as $repository => $params)
        {
            $entity = $em->getRepository($repository);

            foreach ($this->_loot as $key => $value)
            {
                foreach ($params as $field => $options) {
                    $setMethod = 'set' . $field;

                    switch($options['type']) {
                        case 'parsed':
                            $setValue = $value[$options['value']];
                            break;
                        default:
                            $setValue = $options['value'];
                            break;
                    }

                    switch($field) {
                        case 'Text':
                            try {
                                $translator = new Translator(self::YandexApiKey);
                                $textArray = Foundation::toBigPieces($setValue);

                                foreach ($textArray as $key => $textItem) {
                                    $translatedItem = $translator->translate($textItem, 'en-ru', true);
                                    $translatedArray[$key] = $translatedItem;
                                }

                                $setValue = '<p><h3>Source text:</h3></p>' . $setValue . '<p></p><p><h3>Translated text:</h3></p>' . Foundation::fromBigPieces($translatedArray);

                            } catch (Exception $e) {
                                print_r($e);
                            }
                            break;
                        case 'Title':
                            $em->setTitleSeo(assignTitleSeo($setValue));
                            break;
                    }

                    $em->$setMethod($setValue);
                }
                $em->setUserCreated($autobotUser);

                $em->persist($entity);
                $em->flush();
            }
        }

    }
}
