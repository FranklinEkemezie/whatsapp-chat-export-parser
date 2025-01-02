#include <stdbool.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "./HashTable.h"


unsigned int ht_hash(const char *key)
{
    unsigned int hash = 0;
    while (*key) {
        hash = (hash << 5) + *key++;
    }

    return hash % TABLE_SIZE;
}

KeyValue *create_key_value_pair(char *key, void *value)
{
    KeyValue *pair = malloc(sizeof(KeyValue));
    if (! pair)
    {
        fprintf(stderr, "Memory allocation for hash table failed.\n");
        return NULL;
    }

    pair->key = key;
    pair->value = value;

    return pair;
}

HashTable *ht_create()
{
    HashTable *table = malloc(sizeof(HashTable));
    if (! table)
    {
        fprintf(stderr, "Memory allocation for hash table failed.\n");
        return NULL;
    }

    for (int i = 0; i < TABLE_SIZE; i++)
    {
        table->buckets[i] = NULL;
    }
    return table;
}


bool ht_set(HashTable *table, const char *key, void *value)
{
    printf("Setting %s => %i\n", key, *((int *) value));
    unsigned int index = ht_hash(key);

    // Check if the key already exist
    KeyValue *tmp_pair = table->buckets[index];
    while (tmp_pair)
    {
        if (strcmp(key, tmp_pair->key) == 0)
        {

            printf(
                "Key exists. (%i) to be set to (%i)\n",
                *((int *) tmp_pair->value),
                *((int *) value)
            );

            // overwrite it
            void *tmp_value = tmp_pair->value;
            tmp_pair->value = value;
            free(tmp_value);
            
            printf("\n");


            return true;
        }

        tmp_pair = tmp_pair->next;
    }

    KeyValue *new_pair = create_key_value_pair(strdup(key), value);
    if (! new_pair)
    {
        fprintf(stderr, "Memory allocation for key-value pair failed.\n");
        return false;
    }
    new_pair->next  = table->buckets[index];
    
    table->buckets[index] = new_pair;

    printf("\n");

    return true;
}

bool ht_key_exists(HashTable *table, const char *key)
{
    return ht_search(table, key) != NULL;
}


void *ht_search(HashTable *table, const char *key)
{
    unsigned int index = ht_hash(key);
    KeyValue *pair = table->buckets[index];
    while (pair)
    {
        if (strcmp(pair->key, key) == 0)
        {
            return pair->value;
        }
        pair = pair->next;
    }
    
    return NULL;
}


void ht_delete(HashTable *table, const char *key)
{
    unsigned int index = ht_hash(key);
    KeyValue *pair = table->buckets[index];
    KeyValue *prev = NULL;

    while (pair)
    {
        if (strcmp(pair->key, key) == 0)
        {
            if (prev) {
                prev->next = pair->next;
            } else {
                table->buckets[index] = pair->next;
            }

            free(pair->key);
            free(pair->value);
            free(pair);

            return;
        }

        prev = pair;
        pair = pair->next;
    }

    return;
}


void ht_free(HashTable *table)
{
    for (int i = 0; i < TABLE_SIZE; i++)
    {
        KeyValue *pair = table->buckets[i];
        while (pair)
        {
            KeyValue *next = pair->next;

            printf("Freeing key: %s...\n", pair->key);

            free(pair->key);
            free(pair->value);
            free(pair);

            pair = next;
        }
    }
    free(table);
}

void ht_dump(HashTable *table)
{
    printf("{\n");
    for (int i = 0; i < TABLE_SIZE; i++)
    {
        KeyValue *pair = table->buckets[i];
        while (pair)
        {
            // For integers
            printf("\t\"%s\": \"%i\"\n", pair->key, *((int *) pair->value));

            // For strings
            // printf("\t\"%s\": \"%i\"\n", pair->key, (char *) pair->value);

            pair = pair->next;
        }
    }
    printf("}\n");
}