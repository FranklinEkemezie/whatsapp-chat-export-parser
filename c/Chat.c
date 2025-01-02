#include <stdio.h>

#include "./Chat.h"

void dump_chat_info(Chat *chat)
{

    printf("Sender:     %s\n", chat->sender);
    printf("Message:    %s\n", chat->message);
    printf("Timestamp:  %s\n", asctime(localtime(chat->timestamp)));

    printf("\n");
}

void dump_chats_info(Chat **chats, int no_of_chats)
{
    for (
        int i = 0;
        i < no_of_chats * sizeof(Chat *);
        i += sizeof(Chat *)
    ) dump_chat_info(chats[i]);
}