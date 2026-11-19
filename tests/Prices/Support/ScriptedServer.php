<?php

namespace Didww\Tests\Prices\Support;

/**
 * Runs scripted_server.php in a child process.
 */
final class ScriptedServer
{
    private $process;
    private array $pipes = [];
    private string $log;
    private string $repliesFile;
    public readonly string $url;

    /**
     * @param list<list<array{send?: string, sleep?: float}>> $replies one list of steps per connection; "send" is raw bytes
     */
    public function __construct(array $replies)
    {
        $this->log = tempnam(sys_get_temp_dir(), 'didww_wire_');
        $encoded = array_map(static fn (array $steps) => array_map(
            static fn (array $step) => isset($step['send']) ? ['send' => base64_encode($step['send'])] : $step,
            $steps,
        ), $replies);
        $this->repliesFile = tempnam(sys_get_temp_dir(), 'didww_wire_');
        file_put_contents($this->repliesFile, json_encode($encoded));
        $this->process = proc_open(
            [PHP_BINARY, '-n', __DIR__.'/scripted_server.php', $this->log, $this->repliesFile],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $this->pipes,
        );
        $port = trim((string) fgets($this->pipes[1]));
        if (!ctype_digit($port)) {
            throw new \RuntimeException('Scripted server did not start: '.stream_get_contents($this->pipes[2]));
        }
        $this->url = 'http://127.0.0.1:'.$port;
    }

    /**
     * The request heads received, in order.
     *
     * @return list<string>
     */
    public function requests(): array
    {
        $heads = [];
        foreach (file($this->log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $heads[] = json_decode($line, true)['head'];
        }

        return $heads;
    }

    public function stop(): void
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            foreach ($this->pipes as $pipe) {
                fclose($pipe);
            }
            proc_close($this->process);
        }
        @unlink($this->log);
        @unlink($this->repliesFile);
    }

    public function __destruct()
    {
        $this->stop();
    }
}
