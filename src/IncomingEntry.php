<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Wind\Telescope;

use Carbon\Carbon;
use Wind\Telescope\Model\TelescopeEntryModel;
use Wind\Telescope\Model\TelescopeEntryTagModel;

class IncomingEntry
{
    /**
     * The entry's UUID.
     *
     * @var string
     */
    public $uuid;

    /**
     * The entry's batch ID.
     *
     * @var string
     */
    public $batchId;

    /**
     * The entry's sub batch ID.
     *
     * @var string
     */
    public $subBatchId;

    /**
     * The entry's type.
     *
     * @var string
     */
    public $type;

    /**
     * The entry's family hash.
     *
     * @var null|string
     */
    public $familyHash;

    /**
     * The currently authenticated user, if applicable.
     *
     * @var mixed
     */
    public $user;

    /**
     * The entry's content.
     *
     * @var array
     */
    public $content = [];

    /**
     * The entry's tags.
     *
     * @var array
     */
    public $tags = [];

    /**
     * The DateTime that indicates when the entry was recorded.
     *
     * @var string
     */
    public $recordedAt;

    /**
     * Create a new incoming entry instance.
     */
    public function __construct(array $content)
    {
        $this->uuid = (string) Str::orderedUuid();

        $timezone = env('TELESCOPE_TIMEZONE') ?: date_default_timezone_get();
        $this->recordedAt = Carbon::now()->setTimezone($timezone)->toDateTimeString();

        $this->content = array_merge($content, ['hostname' => gethostname()]);

        // $this->tags = ['hostname:'.gethostname()];
    }

    /**
     * Create a new entry instance.
     *
     * @param mixed ...$arguments
     * @return static
     */
    public static function make(...$arguments)
    {
        return new static(...$arguments);
    }

    /**
     * Assign the entry a given batch ID.
     *
     * @return $this
     */
    public function batchId(string $batchId)
    {
        $this->batchId = $batchId;

        return $this;
    }

    /**
     * Assign the entry a given sub batch ID.
     *
     * @return $this
     */
    public function subBatchId(string $batchId)
    {
        $this->subBatchId = $batchId;

        return $this;
    }

    /**
     * Assign the entry a given type.
     *
     * @return $this
     */
    public function type(string $type)
    {
        $this->type = $type;

        if ($type == EntryType::QUERY && $this->content['slow']) {
            $this->tags(['slow']);
        }

        return $this;
    }

    /**
     * Assign the entry a family hash.
     *
     * @return $this
     */
    public function withFamilyHash(string $familyHash)
    {
        $this->familyHash = $familyHash;

        return $this;
    }

    /**
     * Set the currently authenticated user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return $this
     */
    public function user($user = null)
    {
        $authUser = null;
        if (function_exists('auth')) {
            $token = auth()->parseToken();

            if ($token && auth()->check($token)) {
                $authUser = auth()->user();
            }
        }

        $user = $user ?: $authUser;

        if (! is_null($user)) {
            $this->content = array_merge($this->content, [
                'user' => [
                    'id' => $user->getKey(),
                    'name' => $user->name ?? null,
                    'email' => $user->email ?? null,
                ],
            ]);

            $this->tags(['Auth:' . $user->getKey()]);
        }

        $this->user = $user;

        return $this;
    }

    /**
     * Merge tags into the entry's existing tags.
     *
     * @return $this
     */
    public function tags(array $tags)
    {
        $this->tags = array_unique(array_merge($this->tags, $tags));

        return $this;
    }

    /**
     * Determine if the incoming entry has a monitored tag.
     *
     * @return bool
     */
    public function hasMonitoredTag()
    {
        // if (! empty($this->tags)) {
        //     return app(EntriesRepository::class)->isMonitoring($this->tags);
        // }

        return false;
    }

    /**
     * Determine if the incoming entry is a failed request.
     *
     * @return bool
     */
    public function isFailedRequest()
    {
        return $this->type === EntryType::REQUEST &&
            ($this->content['response_status'] ?? 200) >= 500;
    }

    /**
     * Determine if the incoming entry is a query.
     *
     * @return bool
     */
    public function isQuery()
    {
        return $this->type === EntryType::QUERY;
    }

    /**
     * Determine if the incoming entry is a failed job.
     *
     * @return bool
     */
    public function isFailedJob()
    {
        return $this->type === EntryType::JOB &&
            ($this->content['status'] ?? null) === 'failed';
    }

    /**
     * Determine if the incoming entry is a reportable exception.
     *
     * @return bool
     */
    public function isReportableException()
    {
        return false;
    }

    /**
     * Determine if the incoming entry is an exception.
     *
     * @return bool
     */
    public function isException()
    {
        return false;
    }

    /**
     * Determine if the incoming entry is a dump.
     *
     * @return bool
     */
    public function isDump()
    {
        return false;
    }

    /**
     * Determine if the incoming entry is a scheduled task.
     *
     * @return bool
     */
    public function isScheduledTask()
    {
        return $this->type === EntryType::SCHEDULED_TASK;
    }

    /**
     * Get the family look-up hash for the incoming entry.
     *
     * @return null|string
     */
    public function familyHash()
    {
        return $this->familyHash;
    }

    /**
     * Get an array representation of the entry for storage.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'uuid' => $this->uuid,
            'batch_id' => $this->batchId,
            'sub_batch_id' => $this->subBatchId,
            'family_hash' => $this->familyHash,
            'type' => $this->type,
            'content' => $this->content,
            'created_at' => $this->recordedAt,
        ];
    }

    public function create()
    {
        foreach ($this->tags as $tag) {
            $tagItem = [
                'entry_uuid' => $this->uuid,
                'tag' => $tag,
            ];
            TelescopeEntryTagModel::create($tagItem);
        }
        TelescopeEntryModel::create($this->toArray());
    }
}
