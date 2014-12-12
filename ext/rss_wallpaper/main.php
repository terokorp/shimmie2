<?php
/*
 * Name: RSS wallpaper for Win7
 * Author: Thasan
 * Link:
 * License: GPLv2
 * Description: Self explanatory
 */

class RSS_Wallpaper extends Extension {
	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $config, $page;
		$title = $config->get_string('title');

		if(count($event->search_terms) > 0) {
			$search = html_escape(implode(' ', $event->search_terms));
			$page->add_html_header("<link id=\"images\" rel=\"alternate\" type=\"application/rss+xml\" ".
				"title=\"$title - Images with tags: $search\" href=\"".make_link("rss_wallpaper/$search/1")."\" />");
		}
		else {
			$page->add_html_header("<link id=\"images\" rel=\"alternate\" type=\"application/rss+xml\" ".
				"title=\"$title - Images\" href=\"".make_link("rss/images/1")."\" />");
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		if($event->page_matches("rss_wallpaper")) {
			$search_terms = $event->get_search_terms();
			$search_terms[] = "size>=1024x768";
			$search_terms[] = "ratio>=4:3";
//			$search_terms[] = "ratio<=16:9";
			$page_number = $event->get_page_number();
			$page_size = $event->get_page_size();
			$page_size = 200;
			$images = Image::find_images(($page_number-1)*$page_size, $page_size, $search_terms);
			$this->do_rss($images, $search_terms, $page_number);
		}
	}


	private function do_rss($images, $search_terms, /*int*/ $page_number) {
		global $page;
		global $config;
		$page->set_mode("data");
		$page->set_type("application/rss+xml");

		$data = "";
		foreach($images as $image) {
			$link = make_http(make_link("post/view/{$image->id}"));
			$tags = html_escape($image->get_tag_list());
			$owner = $image->get_owner();
			$thumb_url = $image->get_thumb_link();
			$image_url = $image->get_image_link();
			$posted = date(DATE_RSS, $image->posted_timestamp);
			$type = "image/".$image->ext;
			$content = html_escape(
				"<p>" . $this->theme->build_thumb_html($image) . "</p>" .
				"<p>Uploaded by " . html_escape($owner->name) . "</p>"
			);
			$content = "Uploaded by " . html_escape($owner->name);

			$data .= "
		<item>
			<guid isPermaLink=\"true\">$link</guid>
			<title>{$image->id} - $tags</title>
			<link ref=\"http://" . $_SERVER['HTTP_HOST'] . "$image_url\" />
			<enclosure url=\"http://" . $_SERVER['HTTP_HOST'] . "$image_url\" type=\"$type\" />
			<description>$content</description>
			<pubDate>$posted</pubDate>
		</item>
			";
		}


		$title = $config->get_string('title');
		$base_href = make_http(get_base_href());
		$search = "";
		if(count($search_terms) > 0) {
			$search = url_escape(implode(" ", $search_terms)) . "/";
		}

		if($page_number > 1) {
			$prev_url = make_link("rss/images/$search".($page_number-1));
			$prev_link = "<atom:link rel=\"previous\" href=\"$prev_url\" />";
		}
		else {
			$prev_link = "";
		}
		$next_url = make_link("rss/images/$search".($page_number+1));
		$next_link = "<atom:link rel=\"next\" href=\"$next_url\" />"; // no end...

		$version = VERSION;
//    $prev_link
//    $next_link
		$xml = "<"."?xml version=\"1.0\" encoding=\"utf-8\" ?".">
<rss version=\"2.0\">
  <channel>
    <title>$title</title>
    <link>$base_href</link>
    <description>The latest uploads to the image board</description>
    <generator>Shimmie-$version</generator>
    <copyright></copyright>
    $data
  </channel>
</rss>";
		$page->set_data($xml);
	}
}
