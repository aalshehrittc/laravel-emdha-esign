<?php

namespace Tcc\EmdhaEsign\Commands;

use Illuminate\Console\Command;
use Tcc\EmdhaEsign\Services\EmdhaApiService;
use Tcc\EmdhaEsign\Services\EmdhaSdkService;

class EmdhaCheckCommand extends Command
{
    protected $signature = 'emdha:check';

    protected $description = 'Verify EMDHA eSign SDK setup (Java, JAR files, configuration)';

    public function handle(EmdhaSdkService $sdkService, EmdhaApiService $apiService): int
    {
        $this->info('Checking EMDHA eSign SDK setup...');
        $this->newLine();

        $errors = 0;

        $errors += $this->checkJava();
        $errors += $this->checkJarFile($sdkService);
        $errors += $this->checkClasspath($sdkService);
        $errors += $this->checkConfig();
        $errors += $this->checkApiUrl($apiService);
        $errors += $this->checkTempDirectory();

        $this->newLine();

        if ($errors > 0) {
            $this->error("Found {$errors} issue(s). Please fix them before using EMDHA eSign.");
            return self::FAILURE;
        }

        $this->info('All checks passed! EMDHA eSign is ready to use.');
        return self::SUCCESS;
    }

    private function checkJava(): int
    {
        $this->output->write('  Java runtime: ');

        $javaPath = exec('which java 2>/dev/null');

        if (empty($javaPath)) {
            $this->error('NOT FOUND');
            $this->warn('    Install Java (JRE 8+) and ensure `java` is in your PATH.');
            return 1;
        }

        $version = exec('java -version 2>&1 | head -1');

        $this->info("OK ({$version})");

        return 0;
    }

    private function checkJarFile(EmdhaSdkService $sdkService): int
    {
        $this->output->write('  SDK JAR file: ');

        $jarPath = $sdkService->getJarPath();

        if (empty($jarPath)) {
            $this->error('NOT CONFIGURED');
            $this->warn('    Set EMDHA_SDK_JAR_PATH in your .env file.');
            return 1;
        }

        if (!$sdkService->jarExists()) {
            $this->error("NOT FOUND ({$jarPath})");
            $this->warn('    Copy the EMDHA SDK JAR files to a secure directory and set EMDHA_SDK_JAR_PATH.');
            return 1;
        }

        $this->info("OK ({$jarPath})");

        return 0;
    }

    private function checkClasspath(EmdhaSdkService $sdkService): int
    {
        $this->output->write('  SDK classpath: ');

        $classpath = $sdkService->getClasspath();

        if (empty($classpath)) {
            $this->comment('NOT SET (will use -jar mode)');
            return 0;
        }

        $missing = [];
        foreach (explode(PATH_SEPARATOR, $classpath) as $entry) {
            if (!empty($entry) && !file_exists($entry)) {
                $missing[] = $entry;
            }
        }

        if (!empty($missing)) {
            $this->error('MISSING ENTRIES');
            foreach ($missing as $entry) {
                $this->warn("    Missing: {$entry}");
            }
            return 1;
        }

        $this->info("OK ({$classpath})");

        return 0;
    }

    private function checkConfig(): int
    {
        $this->output->write('  SIP ID: ');
        $errors = 0;

        $sipId = config('emdha.sip_id');
        if (empty($sipId)) {
            $this->error('NOT SET');
            $this->warn('    Set EMDHA_SIP_ID in your .env file.');
            $errors++;
        } else {
            $this->info("OK ({$sipId})");
        }

        $this->output->write('  License file: ');
        $licensePath = config('emdha.license_path');
        if (empty($licensePath)) {
            $this->error('NOT SET');
            $this->warn('    Set EMDHA_LICENSE_PATH in your .env file.');
            $errors++;
        } elseif (!file_exists($licensePath)) {
            $this->error("NOT FOUND ({$licensePath})");
            $this->warn('    Copy your EMDHA license file (.lics) and set the path.');
            $errors++;
        } else {
            $this->info("OK ({$licensePath})");
        }

        $this->output->write('  PFX certificate: ');
        $pfxPath = config('emdha.pfx_path');
        if (empty($pfxPath)) {
            $this->error('NOT SET');
            $this->warn('    Set EMDHA_PFX_PATH in your .env file.');
            $errors++;
        } elseif (!file_exists($pfxPath)) {
            $this->error("NOT FOUND ({$pfxPath})");
            $this->warn('    Copy your EMDHA PFX certificate and set the path.');
            $errors++;
        } else {
            $this->info("OK ({$pfxPath})");
        }

        $this->output->write('  PFX password: ');
        if (empty(config('emdha.pfx_password'))) {
            $this->error('NOT SET');
            $this->warn('    Set EMDHA_PFX_PASSWORD in your .env file.');
            $errors++;
        } else {
            $this->info('OK');
        }

        $this->output->write('  PFX alias: ');
        if (empty(config('emdha.pfx_alias'))) {
            $this->error('NOT SET');
            $this->warn('    Set EMDHA_PFX_ALIAS in your .env file.');
            $errors++;
        } else {
            $this->info('OK');
        }

        $this->output->write('  KYC ID provider: ');
        $kycProvider = config('emdha.kyc_id_provider', 'nid');
        $this->info("OK ({$kycProvider})");

        return $errors;
    }

    private function checkApiUrl(EmdhaApiService $apiService): int
    {
        $this->output->write('  API base URL: ');

        $url = $apiService->getBaseUrl();

        if (empty($url)) {
            $this->error('NOT SET');
            $this->warn('    Set EMDHA_BASE_URL in your .env file.');
            return 1;
        }

        $this->info("OK ({$url})");

        return 0;
    }

    private function checkTempDirectory(): int
    {
        $this->output->write('  Temp directory: ');

        $tempPath = config('emdha.temp_path');

        if (empty($tempPath)) {
            $this->error('NOT SET');
            $this->warn('    Set EMDHA_TEMP_PATH in your .env file or ensure storage/app/emdha/temp exists.');
            return 1;
        }

        if (!is_dir($tempPath)) {
            if (!mkdir($tempPath, 0755, true)) {
                $this->error("CANNOT CREATE ({$tempPath})");
                $this->warn('    Create the directory manually or set EMDHA_TEMP_PATH to a writable path.');
                return 1;
            }
        }

        if (!is_writable($tempPath)) {
            $this->error("NOT WRITABLE ({$tempPath})");
            $this->warn('    Ensure the directory is writable by the web server.');
            return 1;
        }

        $this->info("OK ({$tempPath})");

        return 0;
    }
}