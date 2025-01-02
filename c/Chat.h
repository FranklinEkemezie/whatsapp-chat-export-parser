
#ifndef CHAT_H
#define CHAT_H

#include <time.h>

/**
 * Represents a Chat
 */
typedef struct Chat
{
    char *sender;
    char *message;
    time_t *timestamp;
    
}
Chat;

// Dump chat info to the console
void dump_chat_info(Chat *chat);

// Dump chat info for an array of chats to the console
void dump_chats_info(Chat **chats, int no_of_chats);


#endif