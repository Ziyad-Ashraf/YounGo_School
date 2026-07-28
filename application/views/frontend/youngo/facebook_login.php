<div class="youngo-facebook-login">
    <div id="fb-root"></div>
    <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v11.0&appId=<?php echo get_settings('fb_app_id'); ?>&autoLogAppEvents=1"></script>
    <div class="fb-login-button" onlogin="check_API()" data-width="" scope="public_profile,email" data-size="large" data-button-type="continue_with" data-layout="default" data-auto-logout-link="false" data-use-continue-as="true"></div>

    <script>
        function check_API() {
            FB.api('/me', function (response) {
                if (response.name) {
                    FB.getLoginStatus(function (loginResponse) {
                        if (loginResponse.status === 'connected') {
                            location.replace('<?php echo site_url('login/fb_validate_login/'); ?>' + loginResponse.authResponse.accessToken + '/' + loginResponse.authResponse.userID);
                        }
                    });
                }
            });
        }
    </script>
</div>
