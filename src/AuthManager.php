<?php namespace Ollieread\Multiauth;

use Illuminate\Support\Manager;
use Illuminate\Auth\DatabaseUserProvider;
use Illuminate\Auth\EloquentUserProvider;

/**
 * Class AuthManager
 * @package Ollieread\Multiauth
 */
class AuthManager extends Manager
{

    /**
     * Multiauth configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Multiauth provider name.
     *
     * @var string
     */
    protected $name;

    /**
     * @param \Illuminate\Foundation\Application $app
     * @param string $name
     * @param array $config
     */
    public function __construct($app, $name, $config)
    {
        parent::__construct($app);

        $this->config = $config;
        $this->name = $name;
    }

    /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return mixed
     */
    protected function createDriver($driver)
    {
        $guard = parent::createDriver($driver);

        // When using the remember me functionality of the authentication services we
        // will need to be set the encryption instance of the guard, which allows
        // secure, encrypted cookie values to get generated for those cookies.
        if (method_exists($guard, 'setCookieJar')) {
            $guard->setCookieJar($this->app['cookie']);
        }

        if (method_exists($guard, 'setDispatcher')) {
            $guard->setDispatcher($this->app['events']);
        }

        if (method_exists($guard, 'setRequest')) {
            $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
        }

        return $guard;
    }

    /**
     * Call a custom driver creator.
     *
     * @param string $driver
     *
     * @return \Illuminate\Auth\Guard|\Ollieread\Multiauth\Guard
     */
    protected function callCustomCreator($driver)
    {
        $custom = parent::callCustomCreator($driver);

        if ($custom instanceof Guard) {
            return $custom;
        }

        return new Guard($custom, $this->app['session.store'], $this->name);
    }

    /**
     * Create an instance of the database driver.
     *
     * @return \Ollieread\Multiauth\Guard
     */
    public function createDatabaseDriver()
    {
        $provider = $this->createDatabaseProvider();

        return new Guard($provider, $this->app['session.store'], $this->name);
    }

    /**
     * Create an instance of the database user provider.
     *
     * @return \Illuminate\Auth\DatabaseUserProvider
     */
    protected function createDatabaseProvider()
    {
        $connection = $this->app['db']->connection();
        $table = $this->config['table'];

        return new DatabaseUserProvider($connection, $this->app['hash'], $table);
    }

    /**
     * Create an instance of the Eloquent driver.
     *
     * @return \Ollieread\Multiauth\Guard
     */
    public function createEloquentDriver()
    {
        $provider = $this->createEloquentProvider();

        return new Guard($provider, $this->app['session.store'], $this->name);
    }

    /**
     * Create an instance of the Eloquent user provider.
     *
     * @return \Illuminate\Auth\EloquentUserProvider
     */
    protected function createEloquentProvider()
    {
        $model = $this->config['model'];

        return new EloquentUserProvider($this->app['hash'], $model);
    }

    /**
     * Get the default authentication driver name.
     *
     * @return mixed
     */
    public function getDefaultDriver()
    {
        return $this->config['driver'];
    }

    /**
     * Set the default authentication driver name.
     *
     * @param string $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->config['driver'] = $name;
    }

}
