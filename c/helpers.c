#include <stdbool.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>

#include "./Chat.h"
#include "helpers.h"
#include "./utils.h"

typedef struct tm tm;

Chat **parse_chats_from_file(
    char *filename, time_t *from, time_t *to,
    int *no_of_chats, HashTable *chats_per_sender
)
{    
    const int INITIAL_CHATS_LEN = 10;
    int count = 0;  // keep track of how many chats parsed

    *no_of_chats = count;

    Chat **chats = malloc(INITIAL_CHATS_LEN * sizeof(Chat *));
    if (chats == NULL)
    {
        printf("Failed to allocated memory for chats array.\n");
        fprintf(stderr, "Could not parse chats.\n");
        return NULL;
    }
    printf("Successfully allocated %i bytes.\n", INITIAL_CHATS_LEN * sizeof(Chat *));

    FILE *file = fopen(filename, "r");
    if (file == NULL)
    {
        fprintf(stderr, "Could not open file");

        // Cleanup
        free(chats);

        return NULL;
    }

    char *line = NULL;
    size_t line_len;

    // Get the content of the file, line-by-line, 
    // parsing each line to extrac the chat
    int current_chats_len = INITIAL_CHATS_LEN;
    while (getline(&line, &line_len, file) != -1)
    {
        // Parse the chat
        Chat *chat = parse_line(line);
        if (chat == NULL) continue;

        // Update the chats array
        chats[count * sizeof(Chat *)] = chat;
        count++;

        // Update the `chats_per_sender` array if available
        if (chats_per_sender != NULL)
        {
            int *no_of_chats_by_sender = (int *) ht_search(chats_per_sender, chat->sender);
            if (no_of_chats_by_sender == NULL)
            {
                no_of_chats_by_sender = malloc(sizeof(int *));
                if (no_of_chats_by_sender == NULL)
                {
                    printf("Could not allocate memory for `no of chats by sender`\n");
                    fprintf(stderr, "Could not parse chats.\n");

                    // clean-up
                    free(chats);
                    fclose(file);

                    return NULL;
                }

                *no_of_chats_by_sender = 0;
            }

            *no_of_chats_by_sender = (*no_of_chats_by_sender) + 1;

            // printf("No of chats by: %s is: %i\n", chat->sender, *no_of_chats_by_sender);

            if(! ht_set(chats_per_sender, chat->sender, no_of_chats_by_sender))
            {
                printf("Failed to update no of chats by sender.\n");
            }
        }

        // Increase memory allocated to prevent overflow
        if (count >= current_chats_len)
        {
            current_chats_len = count + INITIAL_CHATS_LEN;

            printf("Reallocating to: %i bytes for chat...\n", current_chats_len * sizeof(Chat *));
            Chat **chats_tmp = realloc(chats, current_chats_len * sizeof(Chat *));
            if (chats_tmp == NULL)
            {
                printf("Reallocating memory for chats failed... \n");
                fprintf(stderr, "An error occurred parsing chats.\n");

                // Perform cleanup
                free(chats);
                fclose(file);

                // Update the number of chats parsed so far
                *no_of_chats = count;

                return NULL;
            }

            // Update chats with the memory allocated
            chats = chats_tmp;
        }
    }

    fclose(file);
    *no_of_chats = count;
    return chats;
}

Chat *parse_line(char const *chat_line)
{

    int dd, mm, yyyy, hr, min;
    char period[4];
    char *sender = malloc(512 * sizeof(char));
    if (sender == NULL)
    {
        fprintf(stderr, "Failed to allocate memory for chat sender while parsing chat line.\n");
        return NULL;
    }
    char *message = malloc(512 * sizeof(char));
    if (message == NULL)
    {
        fprintf(stderr, "Failed to allocate memory for chat message while parsing chat line.\n");

        // Cleanup
        free(sender);
        return NULL;
    }

    wchar_t wchr[2];    // weird character

    if (sscanf(
        chat_line, "%d/%d/%d, %d:%d%3lc%2s - %[^:]: %[^\n]",
        &dd, &mm, &yyyy,
        &hr, &min, &wchr, &period,
        sender, message
    ) != 9)
    {
        // Cleanup
        free(sender);
        free(message);

        return NULL;
    }

    hr = period[0] == 'a' && hr != 12 ? (hr + 12) : hr;
    char datetime[20];
    sprintf(
        datetime, "%d-%d-%d %d:%d:%d",
        dd, mm, yyyy, hr, min, 0
    );

    // printf("Weirdo: %lc\n", wchr);

    time_t *timestamp = malloc(sizeof(time_t));
    if (timestamp == NULL)
    {
        fprintf(stderr, "Could not allocate memory for chat timestamp while parsing chat line.\n");

        // Clean up
        free(sender);
        free(message);

        return NULL;
    }
    *timestamp = get_datetime_from_string(datetime);

    Chat *chat = malloc(sizeof(Chat));
    if (chat == NULL)
    {
        fprintf(stderr, "Could not allocate memory for Chat struct.\n");

        // Cleanup
        free(sender);
        free(message);
        free(timestamp);

        return NULL;
    }

    chat->message = message;
    chat->sender = sender;
    chat->timestamp = timestamp;

    return chat;
}


bool save_parse_results(char *filename, Chat *chats[], int no_of_chats, HashTable *chats_per_sender)
{
    
    FILE *file = fopen(filename, "w");
    if (file == NULL)
    {
        fprintf(stderr, "Could not open file to save results.\n");
        return false;
    }

    // printf("Getting summary...\n");
    char *summary = get_summary_from_template(filename, chats, no_of_chats, chats_per_sender);
    // printf("Retrieved summary: %s...\n", summary);

    printf("Writing into file...\n");
    fputs(summary, file);
    printf("File write success\n");

    fclose(file);

    return true;
}


char *get_summary_from_template(
    char *filename, Chat *chats[],
    int no_of_chats, HashTable *chats_per_sender
)
{
    char *summary_template = 
        "************************ SUMMMARY ***************************\n"
        "=============================================================\n"
        "\n"
        "Filename:      %s\n"
        "Total Chats:   %d\n"
        "\n"
        "+-------+-----------------------------------+--------------+\n"
        "|  S/N  | By                                | Total Chats  |\n"
        "+-------+-----------------------------------+--------------+\n"
        "%s\n"
        "+-------+-----------------------------------+--------------+\n"
        "\n"
        "Automatically generated by `whatsapp-chat-export-parser` (PHP) on %s.\n";

    char *chats_summary_template = get_chats_summary_from_template(chats_per_sender);
    
    int summary_len = (
        strlen(summary_template) +
        strlen(chats_summary_template) + 
        16 // just in case
    );

    char *summary = malloc(summary_len * sizeof(char));
    if (summary == NULL)
    {
        fprintf(stderr, "Failed to allocate memory for summary.\n");
        return "Error occured!";
    }

    time_t t = time(NULL);
    sprintf(
        summary,
        summary_template,
        filename, no_of_chats, chats_summary_template, asctime(localtime(&t))
    );

    return summary;
}


char *get_chats_summary_from_template(HashTable *chats_per_sender)
{
    char *chats_summary = malloc(sizeof(char));
    if (chats_summary == NULL)
    {
        printf("Could not allocate memory for chats summary.\n");
        fprintf(stderr, "Failed to get chats summary.\n");
        return NULL;
    }

    char *chat_summary_template = "|  % 3d  | %- 32s  | % 12s |";

    int count = 0;
    for (int i = 0; i < TABLE_SIZE; i++)
    {
        KeyValue *pair = chats_per_sender->buckets[i];
        while (pair)
        {
            char *sender = pair->key;
            int no_of_chats = *((int *) pair->value);
            printf("%s => %i\n", sender, no_of_chats);
            int chat_summary_len = (
                strlen(chat_summary_template)   +
                4                               +   // for serial no.
                strlen(sender)                  +   // for sender
                12                              +   // for no of chats
                16                                  // for just in case 
            );

            char *chat_summary = malloc(chat_summary_len * sizeof(char));
            if (chat_summary == NULL)
            {
                printf("Could not allocate memory for chat.\n");
                fprintf(stderr, "Failed to get chats summary.\n");

                // clean-up
                free(chats_summary);

                return NULL;
            }

            // Format the template and replace with values
            sprintf(
                chat_summary, chat_summary_template,
                ++count, sender, no_of_chats
            );

            // Tie to the chat summary
            int new_size = (strlen(chats_summary) + strlen(chats_summary)) * sizeof(char);
            char *chats_summary_tmp = realloc(chats_summary, new_size * sizeof(char));
            if (chats_summary_tmp == NULL)
            {
                printf("Could not reallocate memory for chats summary.\n");
                fprintf(stderr, "Failed to get chats summary.\n");

                // clean-up
                free(chat_summary);
                free(chats_summary);

                return NULL;
            }

            pair = pair->next;
        }
    }

    return chats_summary;
}