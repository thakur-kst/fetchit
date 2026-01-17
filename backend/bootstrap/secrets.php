<?php

use Aws\SecretsManager\SecretsManagerClient;


/*
|--------------------------------------------------------------------------
| Defaults
|--------------------------------------------------------------------------
*/

$defaults = [
    'region' => 'us-east-1',
    'secret_name' => 'fetchit/dev',
    'endpoint' => 'http://localstack:4566',
    'credentials' => [
        // 'key' => 'test',
        // 'secret' => 'test',
    ],
];

/*
|--------------------------------------------------------------------------
| Load Runtime Config (Optional)
|--------------------------------------------------------------------------
*/

$configPath = __DIR__ . '/../aws-secret.config.json';

if (file_exists($configPath)) {
    $json = json_decode(file_get_contents($configPath), true);
    if (is_array($json)) {
        // Use only values from config file, ignore defaults
        $config = $json;
    } else {
        // Invalid JSON, use defaults
        $config = $defaults;
    }
} else {
    // Config file doesn't exist, use defaults
    $config = $defaults;
}

/*
|--------------------------------------------------------------------------
| Build Client Options
|--------------------------------------------------------------------------
*/

$options = [
    'version' => 'latest',
    'region' => $config['region'],
];

/*
|--------------------------------------------------------------------------
| If endpoint exists → LocalStack
|--------------------------------------------------------------------------
*/
if (!empty($config['endpoint'])) {
    $options['endpoint'] = $config['endpoint'];
    $options['use_path_style_endpoint'] = true;
}


if (!empty($config['credentials']['key']) && !empty($config['credentials']['secret'])) {
    $options['credentials']['key'] = $config['credentials']['key'];
    $options['credentials']['secret'] = $config['credentials']['secret'];
}

/*
|--------------------------------------------------------------------------
| Load Secrets
|--------------------------------------------------------------------------
*/

// Skip secrets loading during composer operations or when explicitly disabled
if (
    (php_sapi_name() === 'cli' && isset($_SERVER['argv']) && (
        in_array('composer', $_SERVER['argv']) || 
        (isset($_SERVER['argv'][0]) && strpos($_SERVER['argv'][0], 'composer') !== false)
    )) ||
    getenv('SKIP_SECRETS_LOADING') === 'true' ||
    (php_sapi_name() === 'cli' && isset($_SERVER['argv']) && 
     isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === 'package:discover')
) {
    return;
}

try {
    $client = new SecretsManagerClient($options);

    $result = $client->getSecretValue([
        'SecretId' => $config['secret_name'],
    ]);

    if (!isset($result['SecretString'])) {
        throw new \RuntimeException("Secret '{$config['secret_name']}' exists but has no SecretString value");
    }

    $secrets = json_decode($result['SecretString'], true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException("Invalid JSON in secret '{$config['secret_name']}': " . json_last_error_msg());
    }

    if (!is_array($secrets)) {
        throw new \RuntimeException("Secret '{$config['secret_name']}' is not a valid JSON object");
    }

    // Load all secrets into environment
    foreach ($secrets as $key => $value) {
        if ($value !== null) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }

} catch (\Aws\SecretsManager\Exception\SecretsManagerException $e) {
    // Secret doesn't exist or other AWS error - fail application startup
    throw new \RuntimeException("Failed to load secret '{$config['secret_name']}' from AWS Secrets Manager: " . $e->getMessage(), 0, $e);
} catch (\Exception $e) {
    // Any other error (network, etc.) - fail application startup
    throw new \RuntimeException("Error loading secrets from AWS Secrets Manager: " . $e->getMessage(), 0, $e);
}