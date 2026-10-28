<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\RouteListCommand as BaseRouteListCommand;
use Illuminate\Support\Arr;

class RouteListCommand extends BaseRouteListCommand
{
    /**
     * The name and signature of the console command.
     *
     * Restores `--columns` for tooling that still passes it (removed from Laravel 13).
     *
     * @var string
     */
    protected $signature = 'route:list
                {--json : Output the route list as JSON}
                {--method= : Filter the routes by method}
                {--action= : Filter the routes by action}
                {--name= : Filter the routes by name}
                {--domain= : Filter the routes by domain}
                {--middleware= : Filter the routes by middleware}
                {--path= : Only show routes matching the given path pattern}
                {--except-path= : Do not display the routes matching the given path pattern}
                {--columns= : Columns to include in the route table (method, uri, name, action, middleware, domain, path)}
                {--r|reverse : Reverse the ordering of the routes}
                {--sort=uri : The column (domain, method, uri, name, action, middleware, definition) to sort by}
                {--except-vendor : Do not display routes defined by vendor packages}
                {--only-vendor : Only display routes defined by vendor packages}';

    /**
     * Get the column names to show (lowercase table headers).
     *
     * @return array
     */
    protected function getColumns()
    {
        $columns = Arr::wrap($this->option('columns'));

        // Laravel 13's CLI formatter requires action/name keys. Only apply
        // column filtering for JSON output; otherwise accept the option for BC.
        if ($this->option('json') && filled($columns[0] ?? null)) {
            return $this->parseColumns($columns);
        }

        return array_map(strtolower(...), $this->headers);
    }
}
