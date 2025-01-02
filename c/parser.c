#include <stdio.h>
#include <time.h>

#include "./Chat.h"
#include "./helpers.h"
#include "./utils.h"

int main(int argc, char *argv[]) {

    if (argc < 2 || argc > 6)
    {
        printf("Usage: ./parser <whatsapp_chat_export.txt> [from_date: dd-mm-yyyy hr:min:sec] [to_date: dd-mm-yyyy hr:min:sec]");
        return 1;
    }

    char *filename;
    time_t from_date, end_date;

    // Get the filename
    filename    = argv[1];

    // Get the from date
    if (argc == 3)
        from_date = get_datetime_from_string(argv[3]);
    else
        from_date = get_datetime_from_string("30-11-2024 00:00:00");
    
    // Get the end date
    if (argc == 4)
        end_date = get_datetime_from_string(argv[4]);
    else
        time(&end_date);

    printf("\n\n---------------------- Start parsing file: %s ------------------------\n\n", filename);


    // Get the chats
    int no_of_chats;
    HashTable *chats_per_sender = ht_create();
    Chat **chats = parse_chats_from_file(
        filename, &from_date, &end_date,
        &no_of_chats, chats_per_sender
    );
    if (chats == NULL)
    {
        if (no_of_chats > 0)
            printf("An error occurred after parsing %i chats from file: %s\n", no_of_chats, filename);
        else
            printf("Could not parse chats from file: %s\n", filename);

        printf("\n\n--------------------- End parsing file :( --------------------------- \n\n");
        return 1;
    }

    printf("Parsed: %i chats.\n", no_of_chats);
    // dump_chats_info(chats, no_of_chats);

    printf("\n\n--------------------- End parsing file :) --------------------------- \n\n");

    ht_dump(chats_per_sender);

    char *res_filename = (argc == 6) ? argv[5] : "../results/c/summary.txt";
    if (! save_parse_results(res_filename, chats, no_of_chats, chats_per_sender))
    {
        printf("Could not save results\n");
        return 2;
    }

    // Clean up
    ht_free(chats_per_sender);

    // Complete.
    printf("Parsed %s successfully.\n", filename);
    return 0;
}