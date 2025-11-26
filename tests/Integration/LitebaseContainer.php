<?php

declare(strict_types=1);

namespace Tests\Integration;

class LitebaseContainer
{
    public static function start(): void
    {
        // Remove any existing container first
        shell_exec('docker rm -f litebase-test 2>/dev/null || true');

        $startCommand = 'docker run -d --rm --name litebase-test -p 8888:8888 \\
            -e LITEBASE_CLUSTER_ID=cluster-1 \\
            -e LITEBASE_DATA_PATH=/tmp/data \\
            -e LITEBASE_ENCRYPTION_KEY=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa \\
            -e LITEBASE_ENV=testing \\
            -e LITEBASE_PORT=8888 \\
            -e LITEBASE_ROOT_USERNAME=root \\
            -e LITEBASE_ROOT_PASSWORD=password \\
            -e LITEBASE_STORAGE_NETWORK_PATH=/tmp/data/_network \\
            -e LITEBASE_STORAGE_TMP_PATH=/tmp \\
            -e LITEBASE_STORAGE_OBJECT_MODE=local \\
            litebase/litebase start';

        shell_exec($startCommand);

        sleep(2);
    }

    public static function stop(): void
    {
        $stopCommand = 'docker stop litebase-test';
        shell_exec($stopCommand);
    }
}
