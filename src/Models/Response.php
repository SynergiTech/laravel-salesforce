<?php

namespace SynergiTech\Salesforce\Models;

use Illuminate\Support\Collection;
use Omniphx\Forrest\Providers\Laravel\Facades\Forrest;
use SynergiTech\Salesforce\Exceptions\InvalidResponseException;

class Response
{
    public int $currentPage;
    public int $totalPages;
    public int $totalSize;
    public bool $done;

    /**
     * @var Collection<int, mixed>
     */
    public Collection $records;

    protected string $nextRecordsUrl;

    /**
     * @param string|array<string, mixed> $rawResponse
     */
    public function __construct(protected string $query, string|array $rawResponse, ?int $currentPage = null)
    {
        if (is_string($rawResponse)) {
            throw new InvalidResponseException('String response received - expected an array');
        } elseif (!array_key_exists('totalSize', $rawResponse)) {
            throw new InvalidResponseException('Response missing totalSize key');
        } elseif (!array_key_exists('done', $rawResponse)) {
            throw new InvalidResponseException('Response missing done key');
        } elseif (!array_key_exists('records', $rawResponse)) {
            throw new InvalidResponseException('Response missing done key');
        }

        /** @var int $totalSize */
        $totalSize = $rawResponse['totalSize'];
        $this->totalSize = $totalSize;

        /** @var bool $done */
        $done = $rawResponse['done'];
        $this->done = $done;

        /** @var array<int, mixed> $records */
        $records = $rawResponse['records'];
        $this->records = collect($records);

        if (array_key_exists('nextRecordsUrl', $rawResponse)) {
            /** @var string $nextRecordsUrl */
            $nextRecordsUrl = $rawResponse['nextRecordsUrl'];
            $this->nextRecordsUrl = $nextRecordsUrl;
        }

        $this->currentPage = $currentPage ? $currentPage : 1;
        $this->totalPages = $this->records->count() === 0 ? 1 : (int) ceil($this->totalSize / $this->records->count());
    }

    /**
     * Retrieve the original query
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Explain the performed query
     *
     * @return string|array<string, mixed>
     */
    public function explain(): string|array
    {
        return Forrest::queryExplain($this->query);
    }

    /**
     * Retrieves the next page of the results if available,
     * if there are no more results then false is returned instead.
     */
    public function nextPage(): self|false
    {
        if (! isset($this->nextRecordsUrl)) {
            return false;
        }

        Forrest::authenticate();
        return new self($this->query, Forrest::next($this->nextRecordsUrl), $this->currentPage + 1);
    }
}
