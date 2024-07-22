<?php

declare(strict_types=1);

namespace App\GraphQL\Resolver;

use App\Document\Event;
use App\DocumentModel\EventModel;
use App\Service\GraphQL\Buffer;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use \ArrayObject;
use App\Service\GlobalId\GlobalIdProvider;
use App\Utility\MethodsBuilder;
use GraphQL\Deferred;
use App\Service\GraphQL\FieldEncryptionProvider;
use Doctrine\Common\Collections\Collection;
use App\Service\GraphQL\GetByFieldValuesQueryArgumentsProvider;

use function in_array;

final class EventResolver implements ResolverInterface
{
    /**
     * Here we store all fields which need a 'complex' field resolver with custom logic.
     * All other fields will be resolved by calling the getter method on the object
     */
    private const COMPLEX_RESOLVER_FIELDS = [
        'id',
        'version',
        'participants',
        'program'
    ];

    public function __construct(
        private EventModel $eventModel,
        private GlobalIdProvider $globalIdProvider,
        private Buffer $buffer,
        private FieldEncryptionProvider $fieldEncryptionProvider,
        private GetByFieldValuesQueryArgumentsProvider $getByFieldValuesQueryArgumentsProvider
    ) {
    }

    public function resolveOneByKey(string $key): Deferred
    {
        $buffer = $this->buffer;
        $model = $this->eventModel;
        $getByFieldValuesQueryArgumentsProvider = $this->getByFieldValuesQueryArgumentsProvider;
        $buffer->add(Event::class, 'key', $key);

        return new Deferred(fn () => $buffer->get(
            Event::class,
            'key',
            $key,
            function ($keys) use ($model, $getByFieldValuesQueryArgumentsProvider): Collection {
                $queryCriteria = $getByFieldValuesQueryArgumentsProvider->toQueryCriteria('key', $keys);
                return $model->getRepository()->find($queryCriteria);
            }
        ));
    }

    /**
     * This magic method is called to resolve each field of the Event type. 
     * @param  Event $event
     */
    public function __invoke(ResolveInfo $info, $event, ArgumentInterface $args, ArrayObject $context): mixed
    {
        if (!in_array($info->fieldName, static::COMPLEX_RESOLVER_FIELDS)) {
            $getterMethodForField = MethodsBuilder::toGetMethod($info->fieldName);
            return $event->$getterMethodForField();
        }
        $getterMethodForField = MethodsBuilder::toResolveMethod($info->fieldName);
        return $this->$getterMethodForField($event, $args);
    }

    private function resolveId(Event $event): string
    {
        return $this->globalIdProvider->toGlobalId($event);
    }

    private function resolveVersion(Event $event): string
    {
        $version = (string) $event->getVersion();
        $id = $event->getId();
        return $this->fieldEncryptionProvider->encrypt($version, $id);
    }

    private function resolveParticipants(Event $event, ArgumentInterface $args): array
    {
        $queryString = isset($args['queryString']) ? strtolower($args['queryString']) : ''; // setting queryString to lowercase in a single statement if queryString exists
        $participants = $event->getParticipants(); 
        if ($queryString !== '') {
            $lowercaseParticipants = array_map('strtolower', $participants); 
            // Precomputed lowercase participants to eliminate stripos internal strtolower calls
            $filteredIndexes = array_keys(array_filter($lowercaseParticipants, function($participant) use ($queryString) {
                return stripos($participant, $queryString) !== false;
            }));
    
            $participants = array_intersect_key($participants, array_flip($filteredIndexes));
        }

        // Explanation:
        // A possible solution here was also:
//      $queryString = strtolower($queryString);
//      $participants = array_filter($participants, function($participant) use ($queryString) {
//          return stripos($participant, $queryString) !== false;
//      });
//         
        //
        // While this code might be simpler to read, and uses less memory - it's not computationally efficient.
        // With the previous approach of minimizing stripos() internal strtolower() calls using a precomputed array of lowercase participants, 
        // redundant operations have been removed since we call strtolower() just once versus calling it for every participant.
        // Note also that array_keys() and array_intersect_key() increase minimally memory usage overhead instead of opting to not use them, but in this case it's well worth it.
        // The benefits of this approach grow with with the size of the dataset.
    
        return $participants;
    }

    private function resolveProgram(Event $event): Collection
    {

        $program = $event->getProgram();
        $sortedProgram = $program->sort(function ($a, $b) {
            return $a->getStartTime() <=> $b->getStartTime();
        });

        // Note that depending on the size of the dataset, we can optimize this further by turning the Doctrine collection to an array, 
        // using usort function for sorting and converting back to a collection. Here is an example:
//      $speeches = $program->toArray();
//      usort($speeches, function ($a, $b) {
//         return $a->getStartTime() <=> $b->getStartTime();
//      });
//      $sortedProgram = new ArrayCollection($speeches);
        // The main advantage of this approach is minimizing Doctrine's object-oriented overhead and other abstractions that negatively impact performance.
        // Worth noting is that the computation overhead in this case it's minimal since Doctrine Collection sort() method already uses usort() internally and that is why I opted to use sort() in my solution.
        
        return $sortedProgram;
    }
}
