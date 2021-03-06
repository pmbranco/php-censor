<?php

namespace PHPCensor\Plugin;

use Exception;
use PDO;
use PHPCensor\Builder;
use PHPCensor\Model\Build;
use PHPCensor\Plugin;

/**
 * SQLite Plugin — Provides access to a SQLite database.
 *
 * @author Dmitry Khomutov <poisoncorpsee@gmail.com>
 */
class Sqlite extends Plugin
{
    /**
     * @var array
     */
    protected $queries = [];

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var array
     */
    protected $pdoOptions = [];

    /**
     * @return string
     */
    public static function pluginName()
    {
        return 'sqlite';
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder, Build $build, array $options = [])
    {
        parent::__construct($builder, $build, $options);

        $buildSettings = $this->builder->getConfig('build_settings');

        if (isset($buildSettings['sqlite'])) {
            $sql        = $buildSettings['sqlite'];
            $this->path = $sql['path'];
        }

        if (!empty($buildSettings['sqlite']['path'])) {
            $this->path = $buildSettings['sqlite']['path'];
        }

        if (!empty($buildSettings['sqlite']['options']) && \is_array($buildSettings['sqlite']['options'])) {
            $this->pdoOptions = $buildSettings['sqlite']['options'];
        }

        if (!empty($this->options['queries']) && \is_array($this->options['queries'])) {
            $this->queries = $this->options['queries'];
        }

        /** @deprecated Queries list without option is deprecated and will be deleted in version 2.0. Use the option "queries" instead. */
        if (!$this->queries) {
            $builder->logWarning(
                '[DEPRECATED] Queries list without option is deprecated and will be deleted in version 2.0. Use the options "queries" instead.'
            );

            foreach ($this->options as $option) {
                if (!\is_array($option)) {
                    $this->queries[] = $this->builder->interpolate($option);
                }
            }
        }
    }

    /**
     * Connects to SQLite and runs a specified set of queries.
     * @return bool
     */
    public function execute()
    {
        try {
            $pdoOptions = array_merge([
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ], $this->pdoOptions);

            $pdo  = new PDO('sqlite:' . $this->path, $pdoOptions);

            foreach ($this->queries as $query) {
                $pdo->query($query);
            }
        } catch (Exception $ex) {
            $this->builder->logFailure($ex->getMessage());
            return false;
        }
        return true;
    }
}
