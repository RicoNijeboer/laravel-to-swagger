<?php

namespace Rico\Swagger\Commands;

use Illuminate\Console\Command;
use Rico\Swagger\Support\Filter;
use Rico\Swagger\Support\RouteFilter;

/**
 * Class FilterCheckCommand
 *
 * @package Rico\Swagger\Commands
 */
class FilterCheckCommand extends Command
{
    protected $signature = 'api:check-filter 
                            { filter : The filter you want to check. }
                            { --route : Check if your filter would compile to a route. }';
    protected $description = 'This command purely exists for you to check if your filter compiles like you want it to.';

    public function handle()
    {
        /** @var Filter $filterClass */
        $filterClass = $this->option('route')
            ? RouteFilter::class
            : Filter::class;

        $filters = $filterClass::extract($this->argument('filter'));

        $this->table(
            ['Type', 'Filter'],
            array_map(
                fn (Filter $filter) => [$filter->getType(), $filter->getFilter()],
                $filters
            )
        );
    }
}