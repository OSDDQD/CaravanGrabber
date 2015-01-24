<?php
namespace Core\GrabberBundle\Caravan;

class MashableCaravan extends Caravan
{

    public function getSourceURL()
    {
        return 'http://feeds.mashable.com/Mashable';
    }

    public function getCaravanInfo()
    {
        $this->curlInit($this->getSourceURL());
        $this->_curlContent = curl_exec($this->_curl);
        $feed = new \SimpleXMLElement($this->_curlContent);
        $this->curlClose();

        $this->setCaravanInfo($feed);

    }

    public function setCaravanInfo($data)
    {
        foreach ($data as $element) {
            foreach ($element->item as $item)
            {
                $this->curlInit($item->link);
                $this->_curlContent = curl_exec($this->_curl);
                $dom = phpQuery::newDocument($this->_curlContent);
                $this->curlClose();
                
                //инициализация DOM phpQuery
                $article = $dom->find('article#story');
                $pq = phpQuery::pq($article);

                //удаление "лишних" элементов
                $pq->find('aside.shares')->remove();
                $pq->find('div.article-info')->remove();
                $pq->find('footer')->remove();
                $pq->find('script')->remove();
                $pq->find('div.inline-ad')->remove();
                $pq->find('div#newsletter-signup')->remove();
                $pq->find('div.right_rail_replace')->remove();
                $pq->find('section.gallery')->remove();
                $pq->find('div.see-also')->remove();

                //парсинг instagram-embed кода
                $instagrams = $pq->find('blockquote.instagram-media');
                foreach($instagrams as $key => $value)
                {
                    phpQuery::pq($value)->replaceWith('<p></p><strong>Изображение скачать по ссылке</strong> <a href="'. phpQuery::pq($value)->find('a')->attr('href') .'">' . phpQuery::pq($value)->find('a')->attr('href') . '</a></p>');
                }

                //парсинг twitter-embed кода
                $tweets = $pq->find('blockquote.twitter-tweet');
                foreach($tweets as $key => $value)
                {
                    phpQuery::pq($value)->replaceWith('<blockquote class="quote-tweet">' . phpQuery::pq($value)->find('p') . '</blockquote>');
                }

                $this->_loot[$pq->attr('data-wp-id')]['title'] = $item->title;
                $this->_loot[$pq->attr('data-wp-id')]['link'] = $item->link;
                $this->_loot[$pq->attr('data-wp-id')]['date'] = $item->pubDate;
                $this->_loot[$pq->attr('data-wp-id')]['id'] = $pq->attr('data-wp-id');
                $this->_loot[$pq->attr('data-wp-id')]['text'] = strip_tags(preg_replace("/(\n){2,}/", "<p></p>", Foundation::removeEmptyHtmlTags($article)), "<p><a><img><blockquote>");

                $images = $pq->find('img');
                foreach ($images as $key => $value)
                {
                    $this->_loot[$pq->attr('data-wp-id')]['image'][$key] = phpQuery::pq($value)->attr('src');
                }
            }
        }
    }

    public function getMap()
    {
        return array(
            'Humor\StructureBundle\Entity\Post' => array(
                'isApproved' => array('type' => 'static', 'value' => false),
                'isVisible' => array('type' => 'static', 'value' => false),
                'Locale' => array('type' => 'static', 'value' => 'ru'),
                'Title' => array('type' => 'parsed', 'value' => 'title'),
                'Text' => array('type' => 'parsed', 'value' => 'text'),
            ),
        );
    }

}
