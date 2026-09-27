<?php

namespace Botble\Analytics;

use Botble\Analytics\Abstracts\AnalyticsAbstract;
use Botble\Analytics\Abstracts\AnalyticsContract;
use Botble\Analytics\Exceptions\InvalidConfiguration;
use Botble\Analytics\Traits\DateRangeTrait;
use Botble\Analytics\Traits\DimensionTrait;
use Botble\Analytics\Traits\FilterByDimensionTrait;
use Botble\Analytics\Traits\FilterByMetricTrait;
use Botble\Analytics\Traits\MetricAggregationTrait;
use Botble\Analytics\Traits\MetricTrait;
use Botble\Analytics\Traits\OrderByDimensionTrait;
use Botble\Analytics\Traits\OrderByMetricTrait;
use Botble\Analytics\Traits\ResponseTrait;
use Botble\Analytics\Traits\RowOperationTrait;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Analytics extends AnalyticsAbstract implements AnalyticsContract
{
    use DateRangeTrait;
    use MetricTrait;
    use DimensionTrait;
    use OrderByMetricTrait;
    use OrderByDimensionTrait;
    use MetricAggregationTrait;
    use FilterByDimensionTrait;
    use FilterByMetricTrait;
    use RowOperationTrait;
    use ResponseTrait;

    public array $orderBys = [];

    public function __construct(int|string $propertyId, string $credentials)
    {
        $this->propertyId = $propertyId;
        $this->credentials = $credentials;
    }

    public function getCredentials(): string
    {
        return $this->credentials;
    }

    public function getClient(): BetaAnalyticsDataClient
    {
        $storage = Storage::disk('local');

        $fileName = 'analytics-credentials.json';

        if (! $storage->exists($fileName) || md5_file($storage->path($fileName)) !== md5($this->getCredentials())) {
            $storage->put('analytics-credentials.json', $this->getCredentials());
        }

        if (! $storage->exists($fileName)) {
            throw new InvalidConfiguration('The credentials file does not exist.');
        }

        return new BetaAnalyticsDataClient([
            'credentials' => $storage->path($fileName),
        ]);
    }

    public function get(): AnalyticsResponse
    {
        $params = [
            'property' => 'properties/' . $this->getPropertyId(),
            'date_ranges' => $this->dateRanges,
            'metrics' => $this->metrics,
            'dimensions' => $this->dimensions,
        ];

        if (! empty($this->orderBys)) {
            $params['order_bys'] = $this->orderBys;
        }

        if (! empty($this->metricAggregations)) {
            $params['metric_aggregations'] = $this->metricAggregations;
        }

        if (! empty($this->dimensionFilter)) {
            $params['dimension_filter'] = $this->dimensionFilter;
        }

        if (! empty($this->metricFilter)) {
            $params['metric_filter'] = $this->metricFilter;
        }

        if (is_int($this->limit)) {
            $params['limit'] = $this->limit;
        }

        if (is_int($this->offset)) {
            $params['offset'] = $this->offset;
        }

        if (is_bool($this->keepEmptyRows)) {
            $params['keep_empty_rows'] = $this->keepEmptyRows;
        }

        $request = new RunReportRequest($params);
        $response = $this->getClient()->runReport($request);

        return $this->formatResponse($response);
    }

    public function fetchMostVisitedPages(Period $period, int $maxResults = 20): Collection
    {
        return $this->dateRange($period)
            ->metrics('screenPageViews')
            ->dimensions(['pageTitle', 'fullPageUrl'])
            ->orderByMetricDesc('screenPageViews')
            ->limit($maxResults)
            ->get()
            ->table;
    }

    public function fetchTopReferrers(Period $period, int $maxResults = 20): Collection
    {
        return $this->dateRange($period)
            ->metrics('screenPageViews')
            ->dimensions('sessionSource')
            ->orderByMetricDesc('screenPageViews')
            ->limit($maxResults)
            ->get()
            ->table;
    }

    public function fetchTopBrowsers(Period $period, int $maxResults = 10): Collection
    {
        return $this->dateRange($period)
            ->metrics('sessions')
            ->dimensions('browser')
            ->orderByMetricDesc('sessions')
            ->get()
            ->table;
    }

    public function performQuery(Period $period, string|array $metrics, string|array $dimensions = []): Collection
    {
        $that = clone $this;

        $query = $that
            ->dateRange($period)
            ->metrics($metrics);

        if ($dimensions) {
            $query = $query->dimensions($dimensions);
        }

        return $query->get()->table;
    }
}
