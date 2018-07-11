<?php
/**
 * Podcast RSS feed template
 *
 * @package WordPress
 * @subpackage SeriouslySimplePodcasting
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $ss_podcasting, $wp_query;

// Hide all errors
error_reporting( 0 );

// Allow feed access by default
$give_access = true;

// Check if feed is password protected
$protection = get_option( 'ss_podcasting_protect', '' );

// Handle feed protection if required
if ( $protection && $protection == 'on' ) {
	
	$give_access = false;
	
	// Request password and give access if correct
	if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) && ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
		$give_access = false;
	} else {
		$username = get_option( 'ss_podcasting_protection_username' );
		$password = get_option( 'ss_podcasting_protection_password' );
		
		if ( $_SERVER['PHP_AUTH_USER'] == $username ) {
			if ( md5( $_SERVER['PHP_AUTH_PW'] ) == $password ) {
				$give_access = true;
			}
		}
	}
}

// Get specified podcast series
$podcast_series = '';
if ( isset( $_GET['podcast_series'] ) && $_GET['podcast_series'] ) {
	$podcast_series = esc_attr( $_GET['podcast_series'] );
} elseif ( isset( $wp_query->query_vars['podcast_series'] ) && $wp_query->query_vars['podcast_series'] ) {
	$podcast_series = esc_attr( $wp_query->query_vars['podcast_series'] );
}

// Get series ID
$series_id = 0;
if ( $podcast_series ) {
	$series    = get_term_by( 'slug', $podcast_series, 'series' );
	$series_id = $series->term_id;
}

// Allow dynamic access control
$give_access = apply_filters( 'ssp_feed_access', $give_access, $series_id );

// Send 401 status and display no access message if access has been denied
if ( ! $give_access ) {
	
	// Set default message
	$message = __( 'You are not permitted to view this podcast feed.', 'seriously-simple-podcasting' );
	
	// Check message option from plugin settings
	$message_option = get_option( 'ss_podcasting_protection_no_access_message' );
	if ( $message_option ) {
		$message = $message_option;
	}
	
	// Allow message to be filtered dynamically
	$message = apply_filters( 'ssp_feed_no_access_message', $message );
	
	$no_access_message = '<div style="text-align:center;font-family:sans-serif;border:1px solid red;background:pink;padding:20px 0;color:red;">' . $message . '</div>';
	
	header( 'WWW-Authenticate: Basic realm="Podcast Feed"' );
	header( 'HTTP/1.0 401 Unauthorized' );
	
	die( $no_access_message );
}

// If redirect is on, get new feed URL and redirect if setting was changed more than 48 hours ago
$redirect     = get_option( 'ss_podcasting_redirect_feed' );
$new_feed_url = false;
if ( $redirect && $redirect == 'on' ) {
	
	$new_feed_url = get_option( 'ss_podcasting_new_feed_url' );
	$update_date  = get_option( 'ss_podcasting_redirect_feed_date' );
	
	if ( $new_feed_url && $update_date ) {
		$redirect_date = strtotime( '+2 days', $update_date );
		$current_date  = time();
		
		// Redirect with 301 if it is more than 2 days since redirect was saved
		if ( $current_date > $redirect_date ) {
			header( 'HTTP/1.1 301 Moved Permanently' );
			header( 'Location: ' . $new_feed_url );
			exit;
		}
	}
}

// If this is a series-sepcific feed, then check if we need to redirect
if ( $series_id ) {
	$redirect     = get_option( 'ss_podcasting_redirect_feed_' . $series_id );
	$new_feed_url = false;
	if ( $redirect && $redirect == 'on' ) {
		$new_feed_url = get_option( 'ss_podcasting_new_feed_url_' . $series_id );
		if ( $new_feed_url ) {
			header( 'HTTP/1.1 301 Moved Permanently' );
			header( 'Location: ' . $new_feed_url );
			exit;
		}
	}
}

// Podcast title
$title = get_option( 'ss_podcasting_data_title', get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_title = get_option( 'ss_podcasting_data_title_' . $series_id, '' );
	if ( $series_title ) {
		$title = $series_title;
	}
}
$title = apply_filters( 'ssp_feed_title', $title, $series_id );

// Podcast description
$description = get_option( 'ss_podcasting_data_description', get_bloginfo( 'description' ) );
if ( $podcast_series ) {
	$series_description = get_option( 'ss_podcasting_data_description_' . $series_id, '' );
	if ( $series_description ) {
		$description = $series_description;
	}
}
$podcast_description = mb_substr( strip_tags( $description ), 0, 3999 );
$podcast_description = apply_filters( 'ssp_feed_description', $podcast_description, $series_id );

// Podcast language
$language = get_option( 'ss_podcasting_data_language', get_bloginfo( 'language' ) );
if ( $podcast_series ) {
	$series_language = get_option( 'ss_podcasting_data_language_' . $series_id, '' );
	if ( $series_language ) {
		$language = $series_language;
	}
}
$language = apply_filters( 'ssp_feed_language', $language, $series_id );

// Podcast copyright string
$copyright = get_option( 'ss_podcasting_data_copyright', '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_copyright = get_option( 'ss_podcasting_data_copyright_' . $series_id, '' );
	if ( $series_copyright ) {
		$copyright = $series_copyright;
	}
}
$copyright = apply_filters( 'ssp_feed_copyright', $copyright, $series_id );

// Podcast subtitle
$subtitle = get_option( 'ss_podcasting_data_subtitle', get_bloginfo( 'description' ) );
if ( $podcast_series ) {
	$series_subtitle = get_option( 'ss_podcasting_data_subtitle_' . $series_id, '' );
	if ( $series_subtitle ) {
		$subtitle = $series_subtitle;
	}
}
$subtitle = apply_filters( 'ssp_feed_subtitle', $subtitle, $series_id );

// Podcast author
$author = get_option( 'ss_podcasting_data_author', get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_author = get_option( 'ss_podcasting_data_author_' . $series_id, '' );
	if ( $series_author ) {
		$author = $series_author;
	}
}
$author = apply_filters( 'ssp_feed_author', $author, $series_id );

// Podcast owner name
$owner_name = get_option( 'ss_podcasting_data_owner_name', get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_owner_name = get_option( 'ss_podcasting_data_owner_name_' . $series_id, '' );
	if ( $series_owner_name ) {
		$owner_name = $series_owner_name;
	}
}
$owner_name = apply_filters( 'ssp_feed_owner_name', $owner_name, $series_id );

// Podcast owner email address
$owner_email = get_option( 'ss_podcasting_data_owner_email', get_bloginfo( 'admin_email' ) );
if ( $podcast_series ) {
	$series_owner_email = get_option( 'ss_podcasting_data_owner_email_' . $series_id, '' );
	if ( $series_owner_email ) {
		$owner_email = $series_owner_email;
	}
}
$owner_email = apply_filters( 'ssp_feed_owner_email', $owner_email, $series_id );

// Podcast explicit setting
$explicit_option = get_option( 'ss_podcasting_explicit', '' );
if ( $podcast_series ) {
	$series_explicit_option = get_option( 'ss_podcasting_explicit_' . $series_id, '' );
	$explicit_option        = $series_explicit_option;
}
$explicit_option = apply_filters( 'ssp_feed_explicit', $explicit_option, $series_id );
if ( $explicit_option && 'on' == $explicit_option ) {
	$itunes_explicit     = 'yes';
	$googleplay_explicit = 'Yes';
} else {
	$itunes_explicit     = 'clean';
	$googleplay_explicit = 'No';
}

// Podcast complete setting
$complete_option = get_option( 'ss_podcasting_complete', '' );
if ( $podcast_series ) {
	$series_complete_option = get_option( 'ss_podcasting_complete_' . $series_id, '' );
	$complete_option        = $series_complete_option;
}
$complete_option = apply_filters( 'ssp_feed_complete', $complete_option, $series_id );
if ( $complete_option && 'on' == $complete_option ) {
	$complete = 'yes';
} else {
	$complete = '';
}

// Podcast cover image
$image = get_option( 'ss_podcasting_data_image', '' );
if ( $podcast_series ) {
	$series_image = get_option( 'ss_podcasting_data_image_' . $series_id, 'no-image' );
	if ( 'no-image' != $series_image ) {
		$image = $series_image;
	}
}
$image = apply_filters( 'ssp_feed_image', $image, $series_id );

// Podcast category and subcategory (all levels) - can be filtered with `ssp_feed_category_output`
$category1 = ssp_get_feed_category_output( 1, $series_id );
$category2 = ssp_get_feed_category_output( 2, $series_id );
$category3 = ssp_get_feed_category_output( 3, $series_id );

// Get stylehseet URL (filterable to allow custom RSS stylesheets)
$stylehseet_url = apply_filters( 'ssp_rss_stylesheet', $ss_podcasting->template_url . 'feed-stylesheet.xsl' );

// Set RSS content type and charset headers
header( 'Content-Type: ' . feed_content_type( 'podcast' ) . '; charset=' . get_option( 'blog_charset' ), true );

// Use `echo` for first line to prevent any extra characters at start of document
echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?>' . "\n";

// Include RSS stylesheet
if ( $stylehseet_url ) {
	echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( $stylehseet_url ) . '"?>';
}

// Get iTunes Type
$itunes_type = get_option( 'ss_podcasting_consume_order' . ( $series_id > 0 ? '_' . $series_id : null ) );


// Podcast owner email address
$owner_email = get_option( 'ss_podcasting_data_owner_email', get_bloginfo( 'admin_email' ) );
if ( $podcast_series ) {
	$series_owner_email = get_option( 'ss_podcasting_data_owner_email_' . $series_id, '' );
	if ( $series_owner_email ) {
		$owner_email = $series_owner_email;
	}
}


// ! ----- オリジナルの改良 ------

// 制限を通過
$restrict_pass   = false;

// 閲覧制限設定を取得
$wc_restrict_ssp = false;   // デフォルトチャネルの場合は、false
$podcast_type = 'ptype_default';
if ( $podcast_series ) {
	$wc_restrict_ssp  = get_option( 'ss_podcasting_wc_restrict_ssp_' . $series_id, false );  // デフォルトは false
	if( $wc_restrict_ssp == 'restrict_enable' ) {
		$wc_restrict_ssp = true;
	}

	$podcast_type = get_option( 'ss_podcasting_podcast_type_' . $series_id, 'ptype_default' );  // デフォルトは false
}

// 閲覧制限なら、アクセス権を調べる
$add_user_message       =  '';   //制限されたシリーズに追加するメッセージ
$add_user_message_email = '';

if ( $wc_restrict_ssp ) {

	// シークレットキーを入手、なければデフォルトを使う
	$wcr_ssp_options = get_option( 'wcr_ssp_options', '' );
	$seckey = isset( $wcr_ssp_options['seckey'] ) &&  ($wcr_ssp_options['seckey'] != '' )  ? $wcr_ssp_options['seckey'] : WCR_SSP_SECKEY;
	
	// token を取り出す
	if( isset($_GET['wcr_token']) && !is_null($_GET['wcr_token']) ){
		
		$de_text = toiee_xor_decrypt( $_GET['wcr_token'] , $seckey  );
		$tmparr  = explode(',', $de_text);
				
		if( isset($tmparr[3]) && is_numeric($tmparr[3]) ){ // user_id があれば
			
			$user_id = $tmparr[3];
			if( ($user = get_userdata( $user_id )) ) {
				
				// user のデータを格納する
				$user_email = $user->user_email;
				$user_lname = $user->last_name;
				$user_fname = $user->first_name;
				
				$add_user_message = " (【ライセンスについて】この教材は、{$user_lname} {$user_fname} ({$user_email}) さんに対してのみ提供しています)";
				$add_user_message_email = " (for ".$user_email.")";
				
				global $wcr_ssp;
				$ret = $wcr_ssp->get_access_and_product_url( $user_email, $user_id, $series_id );
				$restrict_pass = $ret['access'];
				$product_url = $ret['url'];

/*				// 関連商品IDs の取得
				$wc_prods = array();
				foreach( array('product_ids', 'sub_ids', 'mem_ids') as $tmp_field ) {
					$dat = get_option( 'ss_podcasting_' . $tmp_field . '_' . $series_id, false );
					$ids = explode(',' , $dat);
					
					$wc_prods[ $tmp_field ] = $ids;
				}
							
				// 通常商品のチェック
				foreach($wc_prods['product_ids'] as $i)
				{
					$access = wc_customer_bought_product( $user->user_email, $user_id, $i );
					if($access){
						$restrict_pass = true;
						break;
					}
				}
				
				// subscription のチェック
				if ( function_exists('wcs_user_has_subscription') &&  $restrict_pass != true )
				{
					foreach( $wc_prods['sub_ids'] as $i )
					{
						$access = ($i != '') ? wcs_user_has_subscription( $user_id, $i, 'active') : false;
						if( $access ){
							$restrict_pass = true;
							break;
						}
					}
				}
				
				// Membership でチェックする
				if ( function_exists( 'wc_memberships' ) &&  $restrict_pass != true  ) {
					foreach( $wc_prods['mem_ids'] as $i )
					{
						$access = ($i != '') ? wc_memberships_is_user_active_member(  $user_id, $i ) : false;
						if( $access ){
							$restrict_pass = true;
							break;
						}
					}
				}
*/				
			}
		}
	}
}


/*
* # 制限つきコンテンツに対する処理について
*
* $wc_restrict_ssp = true の場合、「制限されている」Series です
* $restrict_pass = true の場合、制限されているコンテンツに対して、アクセス権があることを示します
*
* $wc_restrict_ssp = false の場合、オープンな series なので通常通り表示します
* $wc_restrict_ssp = true の場合、制限付きのため、各エピソードの制限をチェックして、pass していれば表示、
* していなければ、非表示にします。
* 
* また、 $wc_restrict_ssp = true の場合、ユーザーに関する情報を配信タグに埋め込むようにします。
*/
?>
<rss version="2.0"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:wfw="http://wellformedweb.org/CommentAPI/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
     xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
     xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
     xmlns:googleplay="http://www.google.com/schemas/play-podcasts/1.0"
	<?php do_action( 'rss2_ns' ); ?>
>
	
	<channel>
		<title><?php echo esc_html( $title ); ?></title>
		<atom:link href="<?php esc_url( self_link() ); ?>" rel="self" type="application/rss+xml"/>
		<link><?php echo esc_url( apply_filters( 'ssp_feed_channel_link_tag', $ss_podcasting->home_url, $podcast_series ) ) ?></link>
		<description><?php echo esc_html( $description ); ?><?php echo $add_user_message; ?></description>
		<lastBuildDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ); ?></lastBuildDate>
		<language><?php echo esc_html( $language ); ?></language>
		<copyright><?php echo esc_html( $copyright ); ?></copyright>
		<itunes:subtitle><?php echo esc_html( $subtitle ); echo $add_user_message_email; ?></itunes:subtitle>
		<itunes:author><?php echo esc_html( $author ); ?></itunes:author>
		<?php
		if ( $itunes_type ) {
			?>
			<itunes:type><?php echo $itunes_type; ?></itunes:type>
			<?php
		}
		?>
		<googleplay:author><?php echo esc_html( $author ); ?></googleplay:author>
		<googleplay:email><?php echo esc_html( $owner_email ); ?></googleplay:email>
		<itunes:summary><?php echo esc_html( $podcast_description ); ?></itunes:summary>
		<googleplay:description><?php echo esc_html( $podcast_description ); ?></googleplay:description>
		<itunes:owner>
			<itunes:name><?php echo esc_html( $owner_name ); ?></itunes:name>
			<itunes:email><?php echo esc_html( $owner_email ); ?></itunes:email>
		</itunes:owner>
		<itunes:explicit><?php echo esc_html( $itunes_explicit ); ?></itunes:explicit>
		<googleplay:explicit><?php echo esc_html( $googleplay_explicit ); ?></googleplay:explicit>
		<?php if ( $complete ) { ?>
			<itunes:complete><?php echo esc_html( $complete ); ?></itunes:complete><?php }
		if ( $image ) {
			?>
			<itunes:image href="<?php echo esc_url( $image ); ?>"></itunes:image>
			<googleplay:image href="<?php echo esc_url( $image ); ?>"></googleplay:image>
			<image>
				<url><?php echo esc_url( $image ); ?></url>
				<title><?php echo esc_html( $title ); ?></title>
				<link><?php echo esc_url( apply_filters( 'ssp_feed_channel_link_tag', $ss_podcasting->home_url, $podcast_series ) ) ?></link>
			</image>
		<?php }
		if ( isset( $category1['category'] ) && $category1['category'] ) { ?>
			<itunes:category text="<?php echo esc_attr( $category1['category'] ); ?>">
				<?php if ( isset( $category1['subcategory'] ) && $category1['subcategory'] ) { ?>
					<itunes:category text="<?php echo esc_attr( $category1['subcategory'] ); ?>"></itunes:category>
				<?php } ?>
			</itunes:category>
		<?php } ?>
		<?php if ( isset( $category2['category'] ) && $category2['category'] ) { ?>
			<itunes:category text="<?php echo esc_attr( $category2['category'] ); ?>">
				<?php if ( isset( $category2['subcategory'] ) && $category2['subcategory'] ) { ?>
					<itunes:category text="<?php echo esc_attr( $category2['subcategory'] ); ?>"></itunes:category>
				<?php } ?>
			</itunes:category>
		<?php } ?>
		<?php if ( isset( $category3['category'] ) && $category3['category'] ) { ?>
			<itunes:category text="<?php echo esc_attr( $category3['category'] ); ?>">
				<?php if ( isset( $category3['subcategory'] ) && $category3['subcategory'] ) { ?>
					<itunes:category text="<?php echo esc_attr( $category3['subcategory'] ); ?>"></itunes:category>
				<?php } ?>
			</itunes:category>
		<?php } ?>
		<?php if ( $new_feed_url ) { ?>
			<itunes:new-feed-url><?php echo esc_url( $new_feed_url ); ?></itunes:new-feed-url>
		<?php }
		
		// Prevent WP core from outputting an <image> element
		remove_action( 'rss2_head', 'rss2_site_icon' );
		
		// Add RSS2 headers
		do_action( 'rss2_head' );
		
		// Get post IDs of all podcast episodes
		$num_posts = intval( apply_filters( 'ssp_feed_number_of_posts', get_option( 'posts_per_rss', 10 ) ) );
		
		$args = ssp_episodes( $num_posts, $podcast_series, true, 'feed' );

		$qry = new WP_Query( $args );
		
/*  --------------------------------------
	セミナー型の場合、表示される順序を変更する。
	セミナー型の場合、セミナーの最初の episode を最新（rss末尾）にしたい。
	rssの先頭は、セミナーの最後の episode となる。
*/		
/*
		if( $podcast_type == 'ptype_seminar' ) {
			$args['orderby'] = 'post_date';
			$args['order']   = 'DESC';
			
			$last_mod_time = '';
			$episode_count = $qry->found_posts;
		}
		else{
			$args['orderby'] = 'post_date';
			$args['order']   = 'ASC';			
		}
*/
/* ---------------------------------------- */		

		if ( $qry->have_posts() ) {
			while ( $qry->have_posts() ) {
				$qry->the_post();
				
				// Audio file
				$audio_file = $ss_podcasting->get_enclosure( get_the_ID() );
				if ( get_option( 'permalink_structure' ) ) {
					$enclosure = $ss_podcasting->get_episode_download_link( get_the_ID() );
				} else {
					$enclosure = $audio_file;
				}
				
				$enclosure = apply_filters( 'ssp_feed_item_enclosure', $enclosure, get_the_ID() );
				
				// If there is no enclosure then go no further
				if ( ! isset( $enclosure ) || ! $enclosure ) {
					continue;
				}
				
				// Get episode image from post featured image
				$episode_image = '';
				$image_id      = get_post_thumbnail_id( get_the_ID() );
				if ( $image_id ) {
					$image_att = wp_get_attachment_image_src( $image_id, 'full' );
					if ( $image_att ) {
						$episode_image = $image_att[0];
					}
				}
				$episode_image = apply_filters( 'ssp_feed_item_image', $episode_image, get_the_ID() );
				
				// Episode duration (default to 0:00 to ensure there is always a value for this)
				$duration = get_post_meta( get_the_ID(), 'duration', true );
				if ( ! $duration ) {
					$duration = '0:00';
				}
				$duration = apply_filters( 'ssp_feed_item_duration', $duration, get_the_ID() );
				
				// File size
				$size = get_post_meta( get_the_ID(), 'filesize_raw', true );
				
				if ( ! $size ) {
					if ( ssp_is_connected_to_podcastmotor() ) {
						$formatted_size = get_post_meta( get_the_ID(), 'filesize', true );
						$size           = convert_human_readable_to_bytes( $formatted_size );
					} else {
						$size = 1;
					}
				}
				$size = apply_filters( 'ssp_feed_item_size', $size, get_the_ID() );
				
				
				// File MIME type (default to MP3/MP4 to ensure there is always a value for this)
				$mime_type = $ss_podcasting->get_attachment_mimetype( $audio_file );
				if ( ! $mime_type ) {
					
					// Get the episode type (audio or video) to determine the appropriate default MIME type
					$episode_type = $ss_podcasting->get_episode_type( get_the_ID() );
					switch ( $episode_type ) {
						case 'audio':
							$mime_type = 'audio/mpeg';
							break;
						case 'video':
							$mime_type = 'video/mp4';
							break;
					}
				}
				$mime_type = apply_filters( 'ssp_feed_item_mime_type', $mime_type, get_the_ID() );
				
				// Episode explicit flag
				$ep_explicit = get_post_meta( get_the_ID(), 'explicit', true );
				$ep_explicit = apply_filters( 'ssp_feed_item_explicit', $ep_explicit, get_the_ID() );
				if ( $ep_explicit && $ep_explicit == 'on' ) {
					$itunes_explicit_flag     = 'yes';
					$googleplay_explicit_flag = 'Yes';
				} else {
					$itunes_explicit_flag     = 'clean';
					$googleplay_explicit_flag = 'No';
				}
				
				// Episode block flag
				$ep_block = get_post_meta( get_the_ID(), 'block', true );
				$ep_block = apply_filters( 'ssp_feed_item_block', $ep_block, get_the_ID() );
				if ( $ep_block && $ep_block == 'on' ) {
					$block_flag = 'yes';
				} else {
					$block_flag = 'no';
				}
				
				// Episode author
				$author = esc_html( get_the_author() );
				$author = apply_filters( 'ssp_feed_item_author', $author, get_the_ID() );
				
				// Episode content (with iframes removed)
				$content = get_the_content_feed( 'rss2' );
				$content = preg_replace( '/<\/?iframe(.|\s)*?>/', '', $content );
				$content = apply_filters( 'ssp_feed_item_content', $content, get_the_ID() );
				
				// iTunes summary is the full episode content, but must be shorter than 4000 characters
				$itunes_summary = mb_substr( $content, 0, 3999 );
				$itunes_summary = apply_filters( 'ssp_feed_item_itunes_summary', $itunes_summary, get_the_ID() );
				$gp_description = apply_filters( 'ssp_feed_item_gp_description', $itunes_summary, get_the_ID() );
				
				// Episode description
				ob_start();
				the_excerpt_rss();
				$description = ob_get_clean();
				$description = apply_filters( 'ssp_feed_item_description', $description, get_the_ID() );
				
				// iTunes subtitle does not allow any HTML and must be shorter than 255 characters
				$itunes_subtitle = strip_tags( strip_shortcodes( $description ) );
				$itunes_subtitle = str_replace( array(
					'>',
					'<',
					'\'',
					'"',
					'`',
					'[andhellip;]',
					'[&hellip;]',
					'[&#8230;]'
				), array( '', '', '', '', '', '', '', '' ), $itunes_subtitle );
				$itunes_subtitle = mb_substr( $itunes_subtitle, 0, 254 );
				$itunes_subtitle = apply_filters( 'ssp_feed_item_itunes_subtitle', $itunes_subtitle, get_the_ID() );
				
				// Date recorded
				$pubDateType = get_option( 'ss_podcasting_publish_date', 'published' );
				if ( $pubDateType === 'published' ) {
					$pubDate = esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ) );
				} else    // 'recorded'
				{
					$pubDate = esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_post_meta( get_the_ID(), 'date_recorded', true ), false ) );
				}

				// Tags/keywords
				$post_tags = get_the_tags( get_the_ID() );
				if ( $post_tags ) {
					$tags = array();
					foreach( $post_tags as $tag ) {
						$tags[] = $tag->name;
					}
					$tags = apply_filters( 'ssp_feed_item_itunes_keyword_tags', $tags, get_the_ID() );
					if ( ! empty( $tags ) ) {
						$keywords = implode( $tags, ',' );
					}
				}

				$is_itunes_fields_enabled = get_option( 'ss_podcasting_itunes_fields_enabled' );
				if ( $is_itunes_fields_enabled && $is_itunes_fields_enabled == 'on' ) {
					// New iTunes WWDC 2017 Tags
					$itunes_episode_type   = get_post_meta( get_the_ID(), 'itunes_episode_type', true );
					$itunes_title          = get_post_meta( get_the_ID(), 'itunes_title', true );
					$itunes_episode_number = get_post_meta( get_the_ID(), 'itunes_episode_number', true );
					$itunes_season_number  = get_post_meta( get_the_ID(), 'itunes_season_number', true );
				}
				
				
/*  -----------------------------------------------------------------------------------------------
	エピソードの出力は、 $wc_restrict_ssp などを考慮して、決定される。
	$podcast_type = 'ptype_default' の場合は、そのまま出力
	$podcast_type = 'ptype_seminar' の場合は、順番を入れ替え、日付を変えて出力する
*/
				// 一番先頭のepisodeの日付で初期化
				if( $last_mod_time == '' ) { 
					$last_mod_time = strtotime( $pubDate );
				}
				
				// 日付の書き換え（一律7:00 JPT に合わせた）
				if( $podcast_type == 'ptype_seminar' ) {
					$episode_count--;
					$pubDate = date('D, d M Y 20:00:00 +0000' , $last_mod_time - ( $episode_count * 24 * 60 * 60 ) );
				}
				
				// エピソードの制限
				$episode_restrict = get_post_meta( get_the_ID(), 'wcr_ssp_episode_restrict', 'disable' );
				if( $restrict_pass || ($episode_restrict != 'enable' ) ){
					// 表示
					$prefix_episode = '';
					$prefix_episode_description = '';
				}
				else{
					// 非表示にする
					$enclosure = 'https://d.toiee.org/not-available.m4a';
					$prefix_episode = '【会員のみ】 ';
					$prefix_episode_description = '【会員以外の方は、ご利用いただけません（<a href="'.$product_url.'">お申し込みはこちら</a> ）】<br>';	
				}
				
				?>
				<item>
					<title><?php echo $prefix_episode; ?><?php esc_html( the_title_rss() ); ?></title>
					<link><?php esc_url( the_permalink_rss() ); ?></link>
					<pubDate><?php echo $pubDate; ?></pubDate>
					<dc:creator><?php echo $author; ?></dc:creator>
					<guid isPermaLink="false"><?php esc_html( the_guid() ); ?></guid>
					<description><![CDATA[<?php echo $prefix_episode_description . $description; ?>]]></description>
					<itunes:subtitle><![CDATA[<?php echo $itunes_subtitle; ?>]]></itunes:subtitle>
					<?php if ( $keywords ) : ?>
						<itunes:keywords><?php echo $keywords; ?></itunes:keywords>
					<?php endif; ?>
					<?php if ( $itunes_episode_type ) : ?>
						<itunes:episodeType><?php echo $itunes_episode_type; ?></itunes:episodeType>
					<?php endif; ?>
					<?php if ( $itunes_title ): ?>
						<itunes:title><![CDATA[<?php echo $itunes_title; ?>]]></itunes:title>
					<?php endif; ?>
					<?php if ( $itunes_episode_number ): ?>
						<itunes:episode><?php echo $itunes_episode_number; ?></itunes:episode>
					<?php endif; ?>
					<?php if ( $itunes_season_number ): ?>
						<itunes:season><?php echo $itunes_season_number; ?></itunes:season>
					<?php endif; ?>
					<content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded>
					<itunes:summary><![CDATA[<?php echo $itunes_summary; ?>]]></itunes:summary>
					<googleplay:description><![CDATA[<?php echo $gp_description; ?>]]></googleplay:description>
					<?php if ( $episode_image ) { ?>
						<itunes:image href="<?php echo esc_url( $episode_image ); ?>"></itunes:image>
						<googleplay:image href="<?php echo esc_url( $episode_image ); ?>"></googleplay:image>
					<?php } ?>
					<enclosure url="<?php echo esc_url( $enclosure ); ?>" length="<?php echo esc_attr( $size ); ?>" type="<?php echo esc_attr( $mime_type ); ?>"></enclosure>
					<itunes:explicit><?php echo esc_html( $itunes_explicit_flag ); ?></itunes:explicit>
					<googleplay:explicit><?php echo esc_html( $googleplay_explicit_flag ); ?></googleplay:explicit>
					<itunes:block><?php echo esc_html( $block_flag ); ?></itunes:block>
					<googleplay:block><?php echo esc_html( $block_flag ); ?></googleplay:block>
					<itunes:duration><?php echo esc_html( $duration ); ?></itunes:duration>
					<itunes:author><?php echo $author; ?></itunes:author>
				</item>
			<?php }
		} ?>
	</channel>
</rss>