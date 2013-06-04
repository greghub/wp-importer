<?php
/*
Author: Greg Hovanesyan
email: connect.grigor@gmail.com, greg@sevenaristocrats.com
www: http://sevenaristocrats.com/, https://github.com/greghub

This script converts XML files into a format recognized by wp-importer. 
It's used to quickly import XML data into wordpress as posts.
*/


// add this to add CDATA 
class SimpleXMLExtended extends SimpleXMLElement{ 
    public function addCData($string){ 
        $dom = dom_import_simplexml($this);
        $cdata = $dom->ownerDocument->createCDATASection($string);
        $dom->appendChild($cdata);
    } 
}

function xml_Convert_greg( $file ) {

    if (file_exists( $file )) {

        // get contents, clean ampersands
        // you can edit regex to clean other symbols as well
        $xml_i = file_get_contents( $file );
        $xml_i = preg_replace( "/&(?!amp)/", "&amp;", $xml_i );
        
        // write back into the file
        $in = fopen( $file, 'w' );
        fwrite( $in, $xml_i );
        fclose( $in );

        // get XML as an array
        $xml = simplexml_load_file( $file );
        $object = (array) $xml->Results;

        // the first tag of my file, edit this according to your file
        $items = $object['Result'];

        $channel_input = new SimpleXMLExtended('<rss version="2.0"
                      xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
                      xmlns:content="http://purl.org/rss/1.0/modules/content/"
                      xmlns:wfw="http://wellformedweb.org/CommentAPI/"
                      xmlns:dc="http://purl.org/dc/elements/1.1/"
                      xmlns:wp="http://wordpress.org/export/1.2/"
                    ></rss>');

        $channel = $channel_input->addChild( 'channel' );
        $channel->addChild( 'a:wp:wxr_version', '1.2' );
        $channel->addChild( 'generator', 'http://wordpress.org/?v=3.5.1' );

        foreach ( $items as $key => $item ) {

            $item = (array) $item;
           
            $itemXML = $channel->addChild( 'item') ;
            $itemXML->addChild( 'title', $item['NEWS_HEADLINE'] );
            $itemXML->addChild( 'a:dc:creator', 'admin' );
            $itemXML->addChild( 'description' );
            $content = $itemXML->addChild( 'a:content:encoded' );
            $content->addCData( $item['NEWS_TEXT'] );        
            $excerpt = $itemXML->addChild( 'a:excerpt:encoded' );
            $excerpt->addCData( '' );

            // converting date from mm/dd/yyyy to yyyy/mm/dd
            // wordpress accepts yyyy/mm/dd !
            $oldDate = $item['NEWS_DATE'];
            $arr = explode('/', $oldDate);
            $newDate = $arr[2].'-'.$arr[0].'-'.$arr[1];
            $itemXML->addChild( 'a:wp:post_date', $newDate . " 00:00:00" );    

            $itemXML->addChild( 'a:wp:comment_status', 'open' );
            $itemXML->addChild( 'a:wp:ping_status', 'open' );
            $itemXML->addChild( 'a:wp:post_name', $item['NEWS_HEADLINE'] );
            $itemXML->addChild( 'a:wp:status', 'publish' );
            $itemXML->addChild( 'a:wp:post_parent', '0' );
            $itemXML->addChild( 'a:wp:menu_order', '0' );
            $itemXML->addChild( 'a:wp:post_type', 'post' );
            $itemXML->addChild( 'a:wp:post_password' );
            $itemXML->addChild( 'a:wp:is_sticky', '0' );
            $category = $itemXML->addChild( 'category' );

            // a fast way to creat nicename for categories
            $s = array(", ", ",", " ");
            $r = array("-", "-", "-");
            $category->addCData( $item['OUTLET_TYPE'] );
            $category->addAttribute('domain', 'category');
            $category->addAttribute('nicename', str_replace($s, $r, strtolower($item['OUTLET_TYPE']) ));

            $postMeta = $itemXML->addChild( 'a:wp:postmeta' );
            $metaKey = $postMeta->addChild( 'a:wp:meta_key', 'OUTLET_NAME' );
            $meta_value = $postMeta->addChild( 'a:wp:meta_value', htmlspecialchars( $item['OUTLET_NAME'] ));
            $postMeta = $itemXML->addChild( 'a:wp:postmeta' );
            $metaKey = $postMeta->addChild( 'a:wp:meta_key', 'OUTLET_TYPE' );
            $meta_value = $postMeta->addChild( 'a:wp:meta_value', htmlspecialchars( $item['OUTLET_TYPE'] ));
            $postMeta = $itemXML->addChild( 'a:wp:postmeta' );
            $metaKey = $postMeta->addChild( 'a:wp:meta_key', 'NEWS_ATTACHMENT_NAME' );
            $meta_value = $postMeta->addChild( 'a:wp:meta_value', htmlspecialchars( $item['NEWS_ATTACHMENT_NAME'] ) );
            $postMeta = $itemXML->addChild( 'a:wp:postmeta' );
            $metaKey = $postMeta->addChild( 'a:wp:meta_key', 'NEWS_SOURCE' );
            $meta_value = $postMeta->addChild( 'a:wp:meta_value', htmlspecialchars( $item['NEWS_SOURCE'] ) );
            
            // uncomment if needed
            //Header('Content-type: text/xml');

        }
        $res = $channel_input->asXML();

        // write the formatted XML into wordpress to proceed parseing
        $in = fopen( $file, 'w' );
        $fwrite = fwrite( $in, $res );

        return true;
    } else {
        exit('Failed to open test.xml.');
    }
}
?>
