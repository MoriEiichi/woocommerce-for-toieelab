<?php

/*
 * シリーズのIDを渡されたら、Grid表示の教材一覧を表示します。
 */
function w4t_podcast_grid_display( $series_ids ){

	$grid = <<<EOD
		<div class="uk-child-width-1-2@m uk-grid-match" uk-grid>
			<div>
				%COL1%
			</div>
			<div>
				%COL2%
			</div>
		</div>
EOD;

	$rows = count( $series_ids ) / 2 + $series_ids%2;
	$ret = '';

	for($i=0; $i<$rows; $i++){
		$col1 = array_shift( $series_ids );
		$col2 = array_shift( $series_ids );

		$ret .= str_replace(
			array('%COL1%', '%COL2%'),
			array( w4t_podcast_card($col1), w4t_podcast_card($col2) ),
			$grid
		);
	}

	return $ret;
}

function w4t_podcast_card( $sid ){
	if($sid == NULL) return '';

	$card = <<<EOD
                <div class="uk-card uk-card-default uk-card-small uk-card-body">
                    <div class="uk-grid-collapse uk-height-1-1" uk-grid>
                        <div><img src="%IMG%" alt="" width="100px"></div>
                        <div class="uk-width-expand">
                            <h3 class="uk-card-title uk-margin-left">%TITLE%</h3>
                            <p class=" uk-margin-left uk-text-small">%DESCRIPTION%</p>
                        </div>
                    </div>
   	                <a href="%URL%" class="uk-display-block uk-position-cover"></a>
                </div>
EOD;

	$series = get_term( $sid, 'series');
	$series_url   = get_term_link( $series );
	$series_image = get_option( 'ss_podcasting_data_image_' . $sid, 'no-image' );

	return str_replace(
		array('%URL%','%IMG%', '%TITLE%', '%DESCRIPTION%'),
		array($series_url, $series_image, $series->name, $series->description),
		$card
	);
}

/**
 * ポップアップするリダイレクトログインフォームを取得する。
 * 利用する側は、  uk-toggle="target: #modal_login_form" を使う
 *
 * @param $redirect_url
 * @return string
 */
function get_popup_login_form( $redirect_url = null ) {

	if( $redirect_url == null ) {
		$redirect_url = (is_ssl() ? 'https' : 'http') . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
	}

	// error message がある場合、モーダルウィンドウを表示する（ための準備）
	ob_start();
	wc_print_notices();
	$wc_notices = ob_get_contents();
	ob_end_clean();

	$js = ( $wc_notices != '') ?
		"<script>el = document.getElementById('modal_login_form');UIkit.modal(el).show();</script>"
		: '';

	// ログインフォームの取得
	ob_start();
	echo $wc_notices;
	woocommerce_login_form( array('redirect'=> $redirect_url ) );
	echo $js;
	$login_form = ob_get_contents();
	ob_end_clean();

	// 登録フォームの取得
	ob_start();
	?>
	<h2><?php esc_html_e( 'Register', 'woocommerce' ); ?></h2>

	<form method="post" class="woocommerce-form woocommerce-form-register register">

		<?php do_action( 'woocommerce_register_form_start' ); ?>

		<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
			</p>

		<?php endif; ?>

		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
			<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
		</p>

		<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
				<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
			</p>

		<?php endif; ?>

		<?php do_action( 'woocommerce_register_form' ); ?>

		<p class="woocommerce-FormRow form-row">
			<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
			<button type="submit" class="woocommerce-Button button" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
		</p>

		<?php do_action( 'woocommerce_register_form_end' ); ?>

	</form>
	<?php
	$register_form = ob_get_contents();
	ob_end_clean();   //登録フォーム取得、ここまで


	$html = <<<EOD
<!-- This is the modal -->
<div id="modal_login_form" uk-modal>
    <div class="uk-modal-dialog uk-modal-body">
        <h4>会員ログイン</h4>
        <div class="uk-alert-success" uk-alert><p>無料登録で、ご覧になれます。<br>
        <a href="#toggle-form" uk-toggle="target: #toggle-form; animation: uk-animation-fade">無料登録はこちらをクリック</a>
        </p>
        </div>
        <div id="toggle-form" hidden class="uk-card uk-card-default uk-card-body uk-margin-small">
        {$register_form}
        </div>
        {$login_form}
        <p class="uk-text-right">
            <button class="uk-button uk-button-default uk-modal-close" type="button">閉じる</button>
        </p>
    </div>
</div>
EOD;

	return $html;
}

// xor 暗号化
if( ! function_exists('toiee_xor_encrypt') ) {
	function toiee_xor_encrypt($plaintext, $key){
        $len = strlen($plaintext);
        $enc = "";
        for($i = 0; $i < $len; $i++){
                $asciin = ord($plaintext[$i]);
                $enc .= chr($asciin ^ ord($key[$i]));
        }
        $enc = base64_encode($enc);
        return $enc;
	}
}

if( ! function_exists( 'toiee_xor_decrypt' ) ) {	
	function toiee_xor_decrypt($encryptedText, $key){
        $enc = base64_decode($encryptedText);
        $plaintext = "";
        $len = strlen($enc);
        for($i = 0; $i < $len; $i++){
                $asciin = ord($enc[$i]);
                $plaintext .= chr($asciin ^ ord($key[$i]));
        }
        return $plaintext;
	}
}

function toiee_simple_the_content( $text ) {
	
	$text = wptexturize( $text );
	$text = convert_smilies( $text );
	$text = convert_chars( $text );
	$text = wpautop( $text );
	$text = shortcode_unautop( $text );
	$text = do_shortcode( $text );
//	$text = prepend_attachment( $text );
		
	return $text;	
}