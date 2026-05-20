<?php

namespace Tcc\EmdhaEsign\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class EmdhaSdkService
{
    private string $jarPath;
    private string $classpath;
    private int $timeout;
    private string $tempPath;
    private string $licensePath;
    private string $pfxPath;
    private string $pfxPassword;
    private string $pfxAlias;
    private string $sipId;
    private int $contentEstimated;

    public function __construct(array $config)
    {
        $this->jarPath = $config['sdk_jar_path'] ?? '';
        $this->classpath = $config['sdk_classpath'] ?? '';
        $this->timeout = $config['timeout'] ?? 120;
        $this->tempPath = $config['temp_path'] ?? sys_get_temp_dir() . '/emdha';
        $this->licensePath = $config['license_path'] ?? '';
        $this->pfxPath = $config['pfx_path'] ?? '';
        $this->pfxPassword = $config['pfx_password'] ?? '';
        $this->pfxAlias = $config['pfx_alias'] ?? '';
        $this->sipId = $config['sip_id'] ?? '';
        $this->contentEstimated = (int) ($config['content_estimated'] ?? 40000);
    }

    public function generateSignDocRequestXML(array $input): array
    {
        Log::info('EMDHA SDK: GenerateSignDocRequestXML called', [
            'transaction_id' => $input['transactionId'] ?? null,
        ]);

        return $this->callSdkJar('GenerateSignDocRequestXML', $input);
    }

    public function appendSignDocResponse(array $input): array
    {
        Log::info('EMDHA SDK: AppendSignDocResponse called', [
            'transaction_id' => $input['transactionID'] ?? null,
        ]);

        return $this->callSdkJar('AppendSignDocResponse', $input);
    }

    public function buildEaServiceInit(): array
    {
        return [
            'licenseFilePath' => $this->licensePath,
            'pfxFilePath' => $this->pfxPath,
            'pfxPassword' => $this->pfxPassword,
            'pfxAlias' => $this->pfxAlias,
            'sipId' => $this->sipId,
            'contentEstimated' => $this->contentEstimated,
        ];
    }

    private function callSdkJar(string $method, array $input): array
    {
        if (!file_exists($this->jarPath)) {
            Log::error('EMDHA SDK: JAR file not found', ['path' => $this->jarPath]);
            return [
                'status' => 0,
                'errorCode' => 'SDK-0001',
                'errorMessage' => 'EMDHA SDK JAR file not found at: ' . $this->jarPath,
                'transactionID' => null,
            ];
        }

        if (!is_dir($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }

        $inputFile = $this->tempPath . '/' . $method . '_' . uniqid() . '_input.json';
        $outputFile = $this->tempPath . '/' . $method . '_' . uniqid() . '_output.json';

        $jsonInput = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($inputFile, $jsonInput);

        if (!empty($this->classpath) && file_exists($this->classpath)) {
            $command = ['java', '-cp', $this->classpath, 'com.emdha.cli.Main', '-m', $method, '-i', $inputFile, '-o', $outputFile];
        } else {
            $command = ['java', '-jar', $this->jarPath, '-m', $method, '-i', $inputFile, '-o', $outputFile];
        }

        Log::debug('EMDHA SDK: Executing command', [
            'method' => $method,
            'input_file' => $inputFile,
            'output_file' => $outputFile,
            'command' => implode(' ', array_map(fn($a) => str_starts_with($a, '-') ? $a : '"' . $a . '"', $command)),
        ]);

        $process = new Process($command);
        $process->setTimeout($this->timeout);

        try {
            $process->run();

            $stderr = $process->getErrorOutput();
            $stdout = $process->getOutput();

            Log::debug('EMDHA SDK: Process output', [
                'exitCode' => $process->getExitCode(),
                'stdout' => substr($stdout, 0, 1000),
                'stderr' => substr($stderr, 0, 1000),
            ]);

            if (!$process->isSuccessful()) {
                @unlink($inputFile);
                @unlink($outputFile);

                Log::error('EMDHA SDK: Process failed', [
                    'method' => $method,
                    'exitCode' => $process->getExitCode(),
                    'errorOutput' => $stderr,
                ]);

                return [
                    'status' => 0,
                    'errorCode' => 'SDK-0002',
                    'errorMessage' => 'SDK process failed: ' . $stderr,
                    'transactionID' => null,
                ];
            }

            if (!file_exists($outputFile)) {
                @unlink($inputFile);

                Log::error('EMDHA SDK: Output file not created', [
                    'output_file' => $outputFile,
                    'stdout' => substr($stdout, 0, 1000),
                ]);

                return [
                    'status' => 0,
                    'errorCode' => 'SDK-0003',
                    'errorMessage' => 'SDK output file was not created',
                    'transactionID' => null,
                ];
            }

            $outputContent = file_get_contents($outputFile);
            $result = json_decode(trim($outputContent), true);

            @unlink($inputFile);
            @unlink($outputFile);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('EMDHA SDK: Invalid JSON response', [
                    'raw_output' => substr($outputContent, 0, 500),
                ]);

                return [
                    'status' => 0,
                    'errorCode' => 'SDK-0004',
                    'errorMessage' => 'Invalid JSON response from SDK: ' . json_last_error_msg(),
                    'transactionID' => null,
                ];
            }

            Log::info('EMDHA SDK: Command successful', [
                'method' => $method,
                'status' => $result['status'] ?? null,
            ]);

            return $result;

        } catch (\Symfony\Process\Exception\ProcessFailedException $e) {
            @unlink($inputFile);
            @unlink($outputFile);

            Log::error('EMDHA SDK: Process exception', [
                'method' => $method,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 0,
                'errorCode' => 'SDK-0005',
                'errorMessage' => 'SDK process exception: ' . $e->getMessage(),
                'transactionID' => null,
            ];
        }
    }

    public function getJarPath(): string
    {
        return $this->jarPath;
    }

    public function getClasspath(): string
    {
        return $this->classpath;
    }

    public function jarExists(): bool
    {
        return file_exists($this->jarPath);
    }
}