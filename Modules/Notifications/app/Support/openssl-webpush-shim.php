<?php

/**
 * Windows/XAMPP OpenSSL EC-curve shim for the web-push stack.
 *
 * OpenSSL 3 on Windows refuses to create an EC keypair unless it can
 * locate its openssl.cnf. It doesn't honour putenv(OPENSSL_CONF=...)
 * from PHP — only the explicit `config` arg on openssl_pkey_new().
 *
 * The minishlink/web-push and web-token/jwt-library libraries don't
 * pass that arg. PHP's function-name resolution, though, checks the
 * CURRENT NAMESPACE first before falling back to the global one — so
 * we shadow openssl_pkey_new inside the two offending namespaces and
 * inject `config` when the caller didn't supply one.
 *
 * Non-Windows deployments or servers with a proper system openssl.cnf
 * just passthrough to the global function unchanged. Linux production
 * is unaffected.
 *
 * Loaded by NotificationsServiceProvider::register() before any
 * push-related code executes.
 */

namespace Jambo\WebPushShim {
    function resolveOpensslConfPath(): ?string
    {
        static $cached = null;
        if ($cached !== null) return $cached ?: null;

        if (PHP_OS_FAMILY !== 'Windows') {
            $cached = '';
            return null;
        }

        foreach ([
            getenv('OPENSSL_CONF') ?: null,
            'C:/xampp/php/extras/openssl/openssl.cnf',
            'C:/xampp/apache/conf/openssl.cnf',
        ] as $candidate) {
            if ($candidate && is_file($candidate)) {
                $cached = $candidate;
                return $cached;
            }
        }
        $cached = '';
        return null;
    }
}

namespace Minishlink\WebPush {
    function openssl_pkey_new($args = null)
    {
        if (is_array($args) && !isset($args['config'])) {
            $path = \Jambo\WebPushShim\resolveOpensslConfPath();
            if ($path) $args['config'] = $path;
        }
        return \openssl_pkey_new($args);
    }

    function openssl_pkey_export($key, &$output, $passphrase = null, $options = null)
    {
        if ($options === null || !isset($options['config'])) {
            $path = \Jambo\WebPushShim\resolveOpensslConfPath();
            if ($path) {
                $options = is_array($options) ? $options : [];
                $options['config'] = $path;
            }
        }
        return \openssl_pkey_export($key, $output, $passphrase, $options);
    }

    function openssl_csr_new($distinguished_names, &$private_key, $options = null, $extra_attributes = null)
    {
        if ($options === null || !isset($options['config'])) {
            $path = \Jambo\WebPushShim\resolveOpensslConfPath();
            if ($path) {
                $options = is_array($options) ? $options : [];
                $options['config'] = $path;
            }
        }
        return \openssl_csr_new($distinguished_names, $private_key, $options, $extra_attributes);
    }
}

namespace Jose\Component\Core\Util {
    function openssl_pkey_new($args = null)
    {
        if (is_array($args) && !isset($args['config'])) {
            $path = \Jambo\WebPushShim\resolveOpensslConfPath();
            if ($path) $args['config'] = $path;
        }
        return \openssl_pkey_new($args);
    }

    function openssl_pkey_export($key, &$output, $passphrase = null, $options = null)
    {
        if ($options === null || !isset($options['config'])) {
            $path = \Jambo\WebPushShim\resolveOpensslConfPath();
            if ($path) {
                $options = is_array($options) ? $options : [];
                $options['config'] = $path;
            }
        }
        return \openssl_pkey_export($key, $output, $passphrase, $options);
    }

    function openssl_csr_new($distinguished_names, &$private_key, $options = null, $extra_attributes = null)
    {
        if ($options === null || !isset($options['config'])) {
            $path = \Jambo\WebPushShim\resolveOpensslConfPath();
            if ($path) {
                $options = is_array($options) ? $options : [];
                $options['config'] = $path;
            }
        }
        return \openssl_csr_new($distinguished_names, $private_key, $options, $extra_attributes);
    }
}

namespace Jose\Component\KeyManagement {
    function openssl_pkey_new($args = null)
    {
        if (is_array($args) && !isset($args['config'])) {
            $path = \Jambo\WebPushShim\resolveOpensslConfPath();
            if ($path) $args['config'] = $path;
        }
        return \openssl_pkey_new($args);
    }

    function openssl_pkey_export($key, &$output, $passphrase = null, $options = null)
    {
        if ($options === null || !isset($options['config'])) {
            $path = \Jambo\WebPushShim\resolveOpensslConfPath();
            if ($path) {
                $options = is_array($options) ? $options : [];
                $options['config'] = $path;
            }
        }
        return \openssl_pkey_export($key, $output, $passphrase, $options);
    }

    function openssl_csr_new($distinguished_names, &$private_key, $options = null, $extra_attributes = null)
    {
        if ($options === null || !isset($options['config'])) {
            $path = \Jambo\WebPushShim\resolveOpensslConfPath();
            if ($path) {
                $options = is_array($options) ? $options : [];
                $options['config'] = $path;
            }
        }
        return \openssl_csr_new($distinguished_names, $private_key, $options, $extra_attributes);
    }
}
