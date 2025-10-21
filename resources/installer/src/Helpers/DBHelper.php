<?php

namespace Project\Installer\Helpers;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class DBHelper {

    public function create(array $data) {

        $this->updateEnv([
            'DB_CONNECTION'     => "mysql",
            'DB_HOST'           => $data['host'],
            'DB_PORT'           => $data['port'],
            'DB_DATABASE'       => $data['db_name'],
            'DB_USERNAME'       => $data['db_user'],
            'DB_PASSWORD'       => $data['db_user_password'],
        ]);

        $this->setStepSession();
        $this->saveDataInSession($data);

        $helper = new Helper();
        $helper->cache($data);
    }

    public function updateEnv(array $replace_array) {
        $array_going_to_modify  = $replace_array;
        if (count($array_going_to_modify) == 0) {
            return false;
        }

        foreach ($array_going_to_modify as $modify_key => $modify_value) {
            $this->setRuntimeEnv($modify_key, $modify_value);
        }

        $env_file = App::environmentFilePath();
        [$envValues, $orderedKeys] = $this->parseEnvFile($env_file);

        if (empty($envValues)) {
            foreach ($_ENV as $key => $value) {
                $envValues[$key] = $value;
                $orderedKeys[] = $key;
            }
        }

        foreach ($array_going_to_modify as $modify_key => $modify_value) {
            if (!array_key_exists($modify_key, $envValues)) {
                $orderedKeys[] = $modify_key;
            }

            $envValues[$modify_key] = $modify_value;
        }

        $orderedKeys = array_values(array_unique($orderedKeys));

        $lines = [];
        foreach ($orderedKeys as $key) {
            if (!array_key_exists($key, $envValues)) {
                continue;
            }

            $lines[] = $key . "=" . $this->setEnvValue($key, $envValues[$key]);
        }

        sleep(2);
        File::put($env_file, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    public function setEnvValue($key,$value) {
        if($key == "APP_KEY") {
            return $value;
        }

        $normalized = $this->normalizeEnvValue($value);
        $escaped = str_replace(['\\', '"'], ['\\\\', '\"'], $normalized);

        return '"' .$escaped . '"';
    }

    protected function normalizeEnvValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '';
        }

        if (!is_string($value)) {
            return (string) $value;
        }

        return $value;
    }

    protected function setRuntimeEnv(string $key, $value): void
    {
        $normalized = $this->normalizeEnvValue($value);

        putenv($key . '=' . $normalized);
        $_ENV[$key] = $normalized;
        $_SERVER[$key] = $normalized;

        if (function_exists('config')) {
            if ($key === 'APP_ENV') {
                config(['app.env' => $normalized]);
            }

            if ($key === 'APP_DEBUG') {
                $debug = in_array(strtolower($normalized), ['1', 'true', 'on', 'yes'], true);
                config(['app.debug' => $debug]);
            }

            if ($key === 'DB_CONNECTION') {
                config(['database.default' => $normalized ?: config('database.default')]);
            }

            $databaseKeyMap = [
                'DB_HOST'     => 'host',
                'DB_PORT'     => 'port',
                'DB_DATABASE' => 'database',
                'DB_USERNAME' => 'username',
                'DB_PASSWORD' => 'password',
            ];

            if (array_key_exists($key, $databaseKeyMap)) {
                $path = 'database.connections.' . (config('database.default') ?: 'mysql') . '.' . $databaseKeyMap[$key];
                config([$path => $normalized]);
            }
        }
    }

    protected function parseEnvFile(string $path): array
    {
        $values = [];
        $orderedKeys = [];

        if (!File::exists($path)) {
            return [$values, $orderedKeys];
        }

        $contents = File::get($path);
        $lines = preg_split("/\\r\\n|\\n|\\r/", $contents);

        foreach ($lines as $line) {
            if ($line === null) {
                continue;
            }

            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);

            if ($key === '') {
                continue;
            }

            if (!array_key_exists($key, $values)) {
                $orderedKeys[] = $key;
            }

            $values[$key] = $this->normalizeStoredEnvValue($value);
        }

        return [$values, $orderedKeys];
    }

    protected function normalizeStoredEnvValue(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $startsWithQuote = $value[0] === '"' || $value[0] === "'";
        $endsWithQuote = substr($value, -1) === '"' || substr($value, -1) === "'";

        if ($startsWithQuote && $endsWithQuote && strlen($value) >= 2) {
            $value = substr($value, 1, -1);
        }

        $value = str_replace(['\"', '\\\\'], ['"', '\\'], $value);

        return $value;
    }

    public function saveDataInSession($data) {
        session()->put('database_config_data',$data);
    }

    public static function getSessionData() {
        return session('database_config_data');
    }

    public function setStepSession() {
        session()->put("database_config","PASSED");
    }

    public static function step($step = 'database_config') {
        return session($step);
    }

    public function migrate() {

        if(App::environment() != "local") {
            $this->updateEnv([
                'APP_ENV'               => "local",
            ]);

            sleep(2);
        }

        self::execute("php artisan migrate:fresh --seed");
        self::execute("php artisan migrate");
        self::execute("php artisan passport:install");

        $this->setMigrateStepSession();

        // $helper = new Helper();
        // $data = cache()->driver("file")->get($helper->cache_key);

        // update env to production
        $this->updateEnv([
            'APP_ENV'               => "production",
        ]);
    }

    public function setMigrateStepSession() {
        session()->put('migrate','PASSED');
    }

    public function updateAccountSettings(array $data) {

        $helper = new Helper();
        $helper->cache($data);

        $p_key = $helper->cache()['product_key'] ?? "";

        if($p_key == "") {
            cache()->driver('file')->forget($helper->cache_key);
            throw new Exception("Something went wrong! Purchase code registration failed! Please try again");
        }

        $admin = DB::table('admins')->first();
        if(!$admin) {
            DB::table('admins')->insert([
                'firstname'     => $data['f_name'],
                'lastname'      => $data['l_name'],
                'password'      => Hash::make($data['password']),
                'email'         => $data['email'],
            ]);
        }else {
            DB::table("admins")->where('id',$admin->id)->update([
                'firstname'     => $data['f_name'],
                'lastname'      => $data['l_name'],
                'password'      => Hash::make($data['password']),
                'email'         => $data['email'],
            ]);
        }

        $validator = new ValidationHelper();

        if($validator->isLocalInstallation() == false) {
            $helper->connection($helper->cache());
        }

        $client_host = parse_url(url('/'))['host'];
        $filter_host = preg_replace('/^www\./', '', $client_host);

        if(Schema::hasTable('script')) {
            DB::table('script')->truncate();
            DB::table('script')->insert([
                'client'        => $filter_host,
                'signature'     => $helper->signature($helper->cache()),
            ]);
        }
        if(Schema::hasTable('basic_settings')) {
            try{
                DB::table('basic_settings')->where('id',1)->update([
                    'site_name'     => $helper->cache()['app_name'] ?? "",
                ]);
            }catch(Exception $e) {
                //handle error
            }
        }

        $db = new DBHelper();

        $db->updateEnv([
            'PRODUCT_KEY'   => $p_key,
            'APP_MODE'      => "live",
            'APP_DEBUG'     => "false"
        ]);

        $this->setAdminAccountStepSession();

        self::execute("php artisan cache:clear");
        self::execute("php artisan config:clear");
    }

    public function setAdminAccountStepSession() {
        session()->put('admin_account','PASSED');
    }

    public static function execute($cmd): string
    {
        $resolvedCommand = $cmd;

        if (stripos($cmd, 'php ') === 0) {
            $phpBinary = (new PhpExecutableFinder())->find(false);
            if ($phpBinary) {
                $resolvedCommand = '"' . $phpBinary . '"' . substr($cmd, 3);
            }
        }

        $process = Process::fromShellCommandline($resolvedCommand, base_path());

        $processOutput = '';

        $captureOutput = function ($type, $line) use (&$processOutput) {
            $processOutput .= $line;
        };

        $process->setTimeout(null)
            ->run($captureOutput);

        if ($process->getExitCode()) {
            throw new Exception($cmd . " - " . $processOutput);
        }

        return $processOutput;
    }
}
