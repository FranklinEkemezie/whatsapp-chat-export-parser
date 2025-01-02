#ifndef UTILS_H
#define UTILS_H

#include <time.h>

// Get the time from a string in the format: `dd-mm-yyyy hr:min:sec`
time_t get_datetime_from_string(char const *datetime_str);

#endif