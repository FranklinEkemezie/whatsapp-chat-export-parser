#ifndef HELPERS_H
#define HELPERS_H

#include <stdbool.h>
#include <stdlib.h>
#include <time.h>

#include "./Chat.h"
#include "./HashTable.h"

// Parse the chats from the whatsapp chat export file
Chat **parse_chats_from_file(
    char *filename, time_t *from, time_t *to,
    int *no_of_chats, HashTable *chats_per_sender
);

// Parse each line to extract the chat information
Chat *parse_line(char const *chat_line);

// Save the parsed result
bool save_parse_results(char *filename, Chat *chats[], int no_of_chats, HashTable *chats_per_sender);

// Get the summary
char *get_summary_from_template(
    char *filename, Chat *chats[],
    int no_of_chats, HashTable *chats_per_sender
);

// Get the summary for the chats
char *get_chats_summary_from_template(HashTable *chats_per_sender);


#endif