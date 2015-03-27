<?php
/**
 * SolrArticleIndex extension - Inputs page contents into a local solr instance
 *
 * @file
 * @ingroup Extensions
 * @author Pete Olsen 
 * @copyright © 2014 Pete Olsen
 * @license GNU General Public Licence 2.0 or later
 */

class SolrArticleIndexHooks {
	/**
	 * ArticleInsertComplete hook handler.
	 *
	 * @param $article Article
	 * @param $user User
	 * @param $text String: New page text
	 * @return bool
	 */
	public static function articleInsertComplete( $article, $user, $text ) {
                global $wgScriptPath; 

                $post_string = SolrArticleIndexHooks::getPostString($article, $user, $text );
                $url = "http://localhost:8983/solr" . $wgScriptPath . "/update?commit=true";
                #error_log("INSERT POST STRING: $post_string");
                SolrArticleIndexHooks::makeCurlCall( $url, $post_string);                
		return true;
	}

	/**
	 * ArticleSaveComplete hook handler.
	 *
	 * @param $article Article
	 * @param $user User
	 * @param $text String: New page text
	 * @return bool
	 */
	public static function articleSaveComplete( $article, $user, $text ) {
                global $wgScriptPath; 

                $post_string = SolrArticleIndexHooks::getPostString($article, $user, $text);
                $url = "http://localhost:8983/solr" . $wgScriptPath  . "/update?commit=true";
                #error_log("SAVE POST STRING: $post_string");
                SolrArticleIndexHooks::makeCurlCall( $url, $post_string);                
		return true;
	}

	/**
	 * ArticleDeleteComplete hook handler.
	 *
	 * @param $article Article
	 * @param $user User
	 * @param $text String: New page text
	 * @return bool
	 */
	public static function articleDeleteComplete( $article, $user, $text ) {
                global $wgScriptPath; 
                                
		$title = $article->getTitle();
                $urlTitle = $title->getFullURL();

                $post_string = "<delete><query>url:\"$urlTitle\"</query></delete>";
                $url = "http://localhost:8983/solr" . $wgScriptPath  . "/update?commit=true";
                SolrArticleIndexHooks::makeCurlCall( $url, $post_string ); 
		return true;
	}
	/**
	 * ArticleMoveComplete hook handler.
	 *
	 * @param $article Article
	 * @param $user User
	 * @param $text String: New page text
	 * @return bool
	 */
	public static function titleMoveComplete( $title, $new_title, $user, $oldid, $newid ) {

                $article_old = new Article($title);
                $article_new = new Article($new_title);
              
                $old_content = $article_old->getContent();
                $new_content = $article_new->getContent();

		$old_title_url = $title->getFullUrl();

                error_log("Article Insert: ");
                SolrArticleIndexHooks::articleInsertComplete($article_new, $user, $new_content);
                error_log("Article Delete: ");
                SolrArticleIndexHooks::articleDeleteComplete($article_old, $user, $old_content);

		return true;
        }
        
        /***
         *
         * 
         ***/
        private static function getPostString( $article, $user, $text ) {

                global $wgMetaNamespace;                
                global $wgScriptPath; 

		$title = $article->getTitle();
                $urlTitle = $title->getFullURL();
                $revision_and_id = SolrArticleIndexHooks::getRevisionAndIdNumbers($title);
                $revision = $revision_and_id['revision'];
                $pageid = $revision_and_id['pageid'];
                error_log("Post String : text: $text");

                $text = preg_replace("/\n/"," ",$text);
                $text = preg_replace("/\\n/"," ",$text);
                $text = preg_replace("/&/","&amp;",$text);
                $text = preg_replace("/@/"," at ",$text);
                $text = preg_replace("/</","&lt;",$text);
                $text = preg_replace("/>/","&gt;",$text);

                $wiki = $wgScriptPath;
                $wiki = preg_replace("/\//","",$wiki);

                $sections = "";
                $matches = array();
                if ( preg_match_all("/={2,6} ?([^=]*) ?={2,6} ?/", $text ,$matches) ) {
                    error_log("Section MATCHES: " . print_r($matches, 1) );
                    foreach ( $matches[1] as $section_title ) {
                        error_log("Found section : $section_title");
                        $sections .= "<field name='sections'>$section_title</field>";
                    } 
                }

                $cats = "";
                $matches = array();
                if ( preg_match_all("/\[\[Category:([^\]]*)\]\]/", $text ,$matches) ) {
                    foreach ( $matches[1] as $category ) {
                        $cats .= "<field name='categories'>$category</field>";
                    } 
                }

                $post_string =  "<add><doc>";
                $post_string .= "<field name='id'>$pageid</field>";
                $post_string .= "<field name='revision'>$revision</field>";
                if ( $cats ) {
                    $post_string .= $cats;
                }
                $post_string .= "<field name='title'>$title</field>";
                if ( $sections ) {
                    $post_string .= $sections;
                } 
                $post_string .= "<field name='timestamp'>" . date("Y-m-d") . "T" . date("g:i:s")  . "Z</field>";
                $post_string .= "<field name='text'>$text</field>";
                $post_string .= "<field name='user'>$user</field>";
                $post_string .= "<field name='url'>$urlTitle</field>";
                $post_string .=  "</doc></add>";
                
                return $post_string;
        }

        /***
         *
         * 
         ***/
        private static function makeCurlCall( $url, $post_string ) {

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                $header = array("Content-type:text/xml; charset=utf-8");
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($ch, CURLINFO_HEADER_OUT, 1);                
                $result = curl_exec($ch);

                if (curl_errno($ch)) {
                    die("<html><head><title>SEARCH INDEXING EXCEPTION</title><body><pre>curl_error:" . curl_error($ch). "</pre></body></html>");
                } else {
                    curl_close($ch);
                    error_log("Curl Exited OK: $result");
                }

                return $result;
        }

        private static function getRevisionAndIdNumbers( $pageTitle ) {

            global $wgScriptPath; 
            global $wgServer;

            $pageTitle = preg_replace("/ /","_",$pageTitle);

            $api_url= $wgServer . $wgScriptPath . "/api.php?action=query&prop=revisions&format=xml&titles=$pageTitle";
            #error_log("API URL: $api_url");

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt ($ch, CURLOPT_HEADER, 0);
            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                #error_log("API QUERY EXCEPTION : curl_error:" . curl_error($ch));
            } else {
                curl_close($ch);
                error_log("API Curl Exited OK: $result");
            }
            
            preg_match("/revid=\"(\d*)\"/", $result, $matches);
            $revision = $matches[1];
               
            preg_match("/pageid=\"(\d*)\"/", $result, $matches);
            $page_id = $matches[1];

            $return = array('revision' => "$revision", 'pageid' => "$page_id" );
            return $return;
        }
}
