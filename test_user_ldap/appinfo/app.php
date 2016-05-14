<?php

new OCA\test_user_ldap\lib\Hooks();

\OC_User::useBackend(new OCA\test_user_ldap\lib\Backend());