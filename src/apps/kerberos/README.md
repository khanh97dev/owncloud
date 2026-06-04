# Kerberos

This ownCloud enterprise app allows authenticating users using [SPNEGO](https://en.wikipedia.org/wiki/SPNEGO) authentication.

## Documentation

See the [admin documentation](https://doc.owncloud.com/server/latest/admin_manual/enterprise/authentication/kerberos.html) for a comprehensive description with a how-to.

### Configuration

The following options are available to be added to your `confing.php` file: 
```php
<?php $CONFIG = [

    /**
     * path to keytab to use, default is '/etc/krb5.keytab'
     */
    'kerberos.keytab' => '/etc/apache2/www-data.keytab',

    /**
     * timeout before re-enabling spnego based auth after logout, default is 60
     */
    'kerberos.suppress.timeout' => 60,

    /**
     * the domain name - remove from principals to match the pure username
     * e.g. alice@corp.dir will look for the user alice in ldap if 'kerberos.domain' is set to 'corp.dir'
     */
    'kerberos.domain' => '',
    
    /**
     * Name of login button on login page
     */
    'kerberos.login.buttonName' => 'Windows Domain Login',
    
    /**
     * If set to true the login page will immediately try to log in via Kerberos
     */
    'kerberos.login.autoRedirect' => false
];
```

## Todo
- [ ] add mapper mechanism to allow changing the principal, e.g. truncate or lowercase realm?

## Authors

* **Jörn Friedrich Dreyer** - *Initial app* - [butonic](https://github.com/butonic)

## License

This code is covered by the ownCloud Commercial License.

You should have received a copy of the ownCloud Commercial License along with this program.
If not, see https://owncloud.com/licenses/owncloud-commercial/.
