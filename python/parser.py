from helpers import *

def main():

    filename    = get_filename()
    from_date   = datetime(year=2024, month=11, day=1)

    chats       = parse_chats_from_file(filename, from_date, to_date)

    if not save_parse_results(filename, chats):
        print("Could not save results")
    else:
        print(f"Parsed {filename} successfully")

    return

main()