<?php
namespace Portfolion\Cache\Store;

class TaggedCache {
    private StoreInterface $store;
    private array $tags;

    public function __construct(StoreInterface $store, array $tags) {
        $this->store = $store;
        $this->tags = $tags;
    }

    public function get(string $key, mixed $default = null): mixed {
        foreach ($this->tags as $tag) {
            if (!$this->tagExists($tag)) {
                return $default;
            }
        }

        return $this->store->get($this->tagKey($key));
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool {
        $this->storeTags();
        return $this->store->put($this->tagKey($key), $value, $ttl);
    }

    public function forget(string $key): bool {
        return $this->store->forget($this->tagKey($key));
    }

    public function has(string $key): bool {
        foreach ($this->tags as $tag) {
            if (!$this->tagExists($tag)) {
                return false;
            }
        }

        return $this->store->has($this->tagKey($key));
    }

    public function flush(): bool {
        foreach ($this->tags as $tag) {
            $this->store->increment($this->referenceKey($tag));
        }

        return true;
    }

    private function tagKey(string $key): string {
        return implode(':', array_merge($this->tags, [$key]));
    }

    private function referenceKey(string $tag): string {
        return 'tag:' . $tag . ':version';
    }

    private function tagExists(string $tag): bool {
        return $this->store->has($this->referenceKey($tag));
    }

    private function storeTags(): void {
        foreach ($this->tags as $tag) {
            if (!$this->tagExists($tag)) {
                $this->store->forever($this->referenceKey($tag), 1);
            }
        }
    }
}
