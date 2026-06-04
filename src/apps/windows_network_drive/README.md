# Windows Network Drive

An app to seamlessly integrate Windows and Samba/CIFS shared network drives as external storages.

## QA metrics on master branch:

[![Build Status](https://drone.owncloud.com/api/badges/owncloud/windows_network_drive/status.svg)](https://drone.owncloud.com/owncloud/windows_network_drive)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_windows_network_drive&metric=alert_status&token=209ba7740a4f62d94003c52cc7ff9ad4b8d090e5)](https://sonarcloud.io/dashboard?id=owncloud_windows_network_drive)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_windows_network_drive&metric=security_rating&token=209ba7740a4f62d94003c52cc7ff9ad4b8d090e5)](https://sonarcloud.io/dashboard?id=owncloud_windows_network_drive)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_windows_network_drive&metric=coverage&token=209ba7740a4f62d94003c52cc7ff9ad4b8d090e5)](https://sonarcloud.io/dashboard?id=owncloud_windows_network_drive)

## Notice

This app uses a native smb library (libsmbclient) and PHP bindings to that library (libsmbclient-php). You'll need to have both installed and make the bindings available to PHP.

## Kerberos

This app is capable of using Kerberos for authentication. See the [Kerberos](https://doc.owncloud.com/server/latest/admin_manual/enterprise/authentication/kerberos.html) and [WND](https://doc.owncloud.com/server/latest/admin_manual/enterprise/external_storage/windows-network-drive_configuration.html) admin documentation for more details.

## Config.php options

Any necessary `config.php` options for WND can be found in the [config.apps.sample.php](https://github.com/owncloud/core/blob/master/config/config.apps.sample.php) sourced from core respectively the [App: Windows Network Drive (WND)](https://doc.owncloud.com/server/latest/admin_manual/configuration/server/config_apps_sample_php_parameters.html#app-windows-network-drive-wnd) from the admin docs. Note that if there are any config parameter changes necessary, these need to be done in the referenced core file.
