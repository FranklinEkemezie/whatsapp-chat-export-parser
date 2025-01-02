#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>

#include "./utils.h"

typedef struct tm tm;

time_t get_datetime_from_string(char const *datetime_str)
{

    tm tm;
    time_t t;

    // Iniitialize the struct with zero values
    memset(&tm, 0, sizeof(tm));

    // Parse the date using sscanf
    if (sscanf(
        datetime_str, "%d-%d-%d %d:%d:%d",
        &tm.tm_mday, &tm.tm_mon, &tm.tm_year,
        &tm.tm_hour, &tm.tm_min, &tm.tm_sec
    ) != 6)
    {
        fprintf(stderr, "Error parsing date.\n");
        return -1;
    }

    // Adjust for month (0 - 11); and years (starting from 1900)
    tm.tm_mon   -= 1;
    tm.tm_year  -= 1900;

    // Convert struct tm to time_t
    t = mktime(&tm);
    if (t == -1)
    {
        fprintf(stderr, "Error converting time.\n");
        return -1;
    }

    return t;
}
