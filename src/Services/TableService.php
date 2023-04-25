<?php

namespace SynergiTech\Salesforce\Services;

use Illuminate\Support\Collection;
use Omniphx\Forrest\Exceptions\SalesforceException;
use Omniphx\Forrest\Providers\Laravel\Facades\Forrest;
use stdClass;
use SynergiTech\Salesforce\Exceptions\EntityIsDeletedException;
use SynergiTech\Salesforce\Exceptions\InvalidCrossReferenceKeyException;
use SynergiTech\Salesforce\Exceptions\InvalidFieldException;
use SynergiTech\Salesforce\Exceptions\MalformedIdException;
use SynergiTech\Salesforce\Exceptions\MalformedQueryException;
use SynergiTech\Salesforce\Exceptions\NotFoundException;
use SynergiTech\Salesforce\Exceptions\RequiredFieldMissingException;
use SynergiTech\Salesforce\Models\Builder;
use SynergiTech\Salesforce\Models\Response;

class TableService extends Builder
{
    /**
     * Retrieves one or more available objects from Salesforce by Id
     *
     * @param int|string $id
     * @return array<mixed>
     */
    public function find(int|string $id, string $fieldName = 'Id'): array
    {
        $this->where($fieldName, $id);
        $response = $this->get();

        if ($response->records->count() === 0) {
            throw new NotFoundException("A record with the ID '{$id}' could not be found");
        }

        /** @var array<mixed> $result */
        $result = $response->records->first();

        return $result;
    }

    /**
     * Retrieves one or more available objects from Salesforce by Id
     *
     * @param int|string|array<int|string> $id
     * @return Collection<int, mixed>
     */
    public function findMany(int|string|array $id, string $fieldName = 'Id'): Collection
    {
        $id = is_array($id) ? $id : [$id];
        $this->whereIn($fieldName, $id);
        $response = $this->get();

        if ($response->records->count() === 0) {
            throw new NotFoundException("No record(s) with the specified ID(s) could be found");
        }

        return $response->records;
    }

    /**
     * Execute the query and retrieve available objects in a response
     */
    public function get(): Response
    {
        try {
            $query = $this->getQuery();
            return new Response($query, Forrest::query($query));
        } catch (SalesforceException $ex) {
            $this->throwException($ex);
            throw $ex;
        }
    }

    /**
     * Create a record in the Salesforce table with the specified data
     * 
     * @return array<string, mixed>|false
     */
    public function create(array $data = []): array|false
    {
        try {
            $response = Forrest::sobjects($this->table, [
                'method' => 'post',
                'body' => $data,
            ]);

            if ($response['success'] ?? false) {
                $response['data'] = $this->find($response['id']);
                return $response;
            }
        } catch (SalesforceException $ex) {
            $this->throwException($ex);
            throw $ex;
        }

        return false;
    }

    /**
     * Update a record with the specified Id in Salesforce with the provided data
     * 
     * @return array<string, mixed>|false
     */
    public function update(string $id, array $data): array|false
    {
        try {
            Forrest::sobjects(implode('/', [
                $this->table,
                $id,
            ]), [
                'method' => 'patch',
                'body' => $data,
            ]);

            return $this->find($id);
        } catch (SalesforceException $ex) {
            $this->throwException($ex);
            throw $ex;
        }
        
        return false;
    }

    /**
     * Upsert a record using the specified external Id field and Id with the provided data
     * 
     * @return array<string, mixed>|false
     */
    public function createOrUpdate(string $field, string $id, array $data = []): array|false
    {
        try {
            $response = Forrest::sobjects(implode('/', [
                $this->table,
                $field,
                $id,
            ]), [
                'method' => 'patch',
                'body' => $data,
            ]);

            if ($response['success'] ?? false) {
                $response['data'] = $this->find($response['id']);
                return $response;
            }
        } catch (SalesforceException $ex) {
            $this->throwException($ex);
            throw $ex;
        }

        return false;
    }

    /**
     * Delete a record on the table using the specified Id
     * 
     * @return bool
     */
    public function delete(string $id): bool
    {
        try {
            Forrest::sobjects(implode('/', [
                $this->table,
                $id,
            ]), [
                'method' => 'delete',
            ]);
            return true;
        } catch (SalesforceException $ex) {
            $this->throwException($ex);
            throw $ex;
        }

        return false;
    }

    protected function throwException(SalesforceException $ex): void
    {
        $error = $this->decodeError($ex);

        if (property_exists($error, 'errorCode')) {
            $message = $this->formatErrorMessage($error->message);

            switch ($error->errorCode) {
                case 'ENTITY_IS_DELETED':
                    throw new EntityIsDeletedException($message);
                case 'INVALID_CROSS_REFERENCE_KEY':
                    throw new InvalidCrossReferenceKeyException($message);
                case 'INVALID_ID_FIELD':
                    throw new InvalidFieldException($message);
                case 'INVALID_QUERY_FILTER_OPERATOR':
                    throw new InvalidFieldException($message);
                case 'REQUIRED_FIELD_MISSING':
                    throw new RequiredFieldMissingException($message);
                case 'MALFORMED_QUERY':
                    throw new MalformedQueryException($message);
                case 'MALFORMED_ID':
                    throw new MalformedIdException($message);
                case 'NOT_FOUND':
                    throw new NotFoundException($message);
            }
        }
    }

    /**
     * @return stdClass{errorCode:string, message:string}
     */
    protected function decodeError(SalesforceException $ex): stdClass
    {
        $message = $ex->getMessage();
        /** @var array<mixed> $errors */
        $errors = json_decode($message);
        /** @var stdClass{errorCode:string, message:string} $error */
        return $errors[0];
    }

    protected function formatErrorMessage(string $message): string
    {
        return str_replace('\n', ' - ', $message);
    }
}
