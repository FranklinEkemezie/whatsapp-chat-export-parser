#ifndef HASH_TABLE_H
#define HASH_TABLE_H

#define TABLE_SIZE 100

/**
 * Represents a Key-Value pair structure
 */
typedef struct KeyValue {
    char *key;
    void *value;
    struct KeyValue *next;
} KeyValue;

/**
 * Represents a Hash table structure
 */
typedef struct HashTable
{
    KeyValue *buckets[TABLE_SIZE];
} HashTable;


// Create a new key value pair structure
KeyValue *create_key_value_pair(char *key, void *value);

// Hash function
unsigned int ht_hash(const char *key);

// Create a new hash table
HashTable *ht_create();

// Insert a key-value pair into the hash table
bool ht_set(HashTable *table, const char *key, void *value);

// Check if key exists
bool ht_key_exists(HashTable *table, const char *key);

// Search for a value by key
void *ht_search(HashTable *table, const char *key);

// Delete a key-value pair
void ht_delete(HashTable *table, const char *key);

// Free the hash table
void ht_free(HashTable *table);

// Dump the hash table
// Suitable for string value
void ht_dump(HashTable *table);


#endif