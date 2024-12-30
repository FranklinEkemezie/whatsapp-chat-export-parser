import os
import re
import json
from datetime import datetime

from Chat import Chat

def get_filename() -> str:
    """Get the filename from the command line"""

    root_dir = os.path.dirname(os.getcwd())

    return f"{root_dir}{os.sep}samples{os.sep}mce.txt"


def parse_chats_from_file(filename: str, from_date: datetime|None, to_date: datetime|None) -> list:
    """Parse the chats from the file"""

    chats = []

    if not os.path.isfile(filename):
        raise FileNotFoundError("File not found")
    
    with open(filename, "r", encoding="utf-8") as file:
        while True:
            line = file.readline()
            if not line:
                break

            chat = parse_line(line)
            if not chat:
                continue


            if from_date is not None and not chat.sent_after(from_date):
                continue

            chats.append(chat)
    
    return chats


def parse_line(line: str) -> Chat:
    """Parse line to extract message"""

    date_regex      = "\d\d\/\d\d\/\d\d\d\d"
    time_regex      = "(\d?\d:\d\d).+([a|p]m)"
    sender_regex    = ".+?"
    message_regex   = ".+"
    
    chat_regex      = re.compile(f"({date_regex}), ({time_regex}) - ({sender_regex}): ({message_regex})")

    match = re.match(chat_regex, line)
    if match == None:
        return None
    
    # Parse date in the form 'dd-mm-yyyy- hr:min a|pm'
    dd, mm, yyyy = match.group(1).split("/")
    hr, min = match.group(3).split(":")
    am_pm = match.group(4)

    dd, mm, yyyy = int(dd), int(mm), int(yyyy)
    hr, min = int(hr), int(min)
    hr = (hr + 12) if am_pm == "pm" and hr > 12 else hr

    chat_timestamp = datetime(year=yyyy, month=mm, day=dd, hour=hr, minute=min)

    return Chat(match.group(5), match.group(6), chat_timestamp)


def sort_chats_by_sender(chats: list, keep_chat_as_obj: bool = False) -> list:
    """Sort te chats by sender"""
    sorted_chats = {}

    for chat in chats:
        sender      = chat.sender
        sender_chats= sorted_chats.get(sender)

        if sender_chats is None:
            sender_chats = []

        sender_chats.append(chat if keep_chat_as_obj else chat.__dict__())
        sorted_chats[sender] = sender_chats
    
    return sorted_chats


def get_chats_per_sender(chats: list, sort_asc: None|bool = None) -> dict:
    """Get the number of chats per sender"""

    chats_by_sender     = sort_chats_by_sender(chats)
    chats_per_sender    = {
        sender: len(sender_chats) for sender, sender_chats in chats_by_sender.items()
    }

    # Sort the chats by number of chats
    def get_no_chats_per_sender(sender_no_of_chats):
        _, no_of_chats = sender_no_of_chats
        return no_of_chats
    
    # Convert object to list and sort
    senders_no_of_chats_list = list(chats_per_sender.items())
    senders_no_of_chats_list.sort(key=get_no_chats_per_sender, reverse=not sort_asc)

    return {
        sender: no_of_chats for sender, no_of_chats in 
        senders_no_of_chats_list
    }


def save_parse_results(filename: str, chats: list) -> bool:
    """Save the parsed results to files"""

    root_dir    = os.path.dirname(os.getcwd())
    results_dir = f"{root_dir}{os.sep}results"
    if not os.path.isdir(results_dir):
        try:
            os.mkdir(results_dir)
        except:
            print("Could not save results")
            return False
    
    python_result_dir = f"{results_dir}{os.sep}python"
    if not os.path.isdir(python_result_dir):
        try:
            os.mkdir(python_result_dir)
        except:
            print("Could not save results")
            return False
    
    file_basename = os.path.splitext(
        str(os.path.basename(filename))
    )[0]
    result_dir = f"{python_result_dir}{os.path.sep}{file_basename}"
    if not os.path.isdir(result_dir):
        try:
            os.mkdir(result_dir)
        except:
            print("Could not save results")
            return False
    
    # Send in the results (to chats.json)
    json_filename   = f"{result_dir}{os.path.sep}chats.json"
    with open(json_filename, "w", encoding="utf-8") as json_file:
        json_file.write(
            json.dumps([chat.__dict__() for chat in chats], indent=4)
        )
    
    # Send in the summary to summary.txt
    summary_filename = f"{result_dir}{os.path.sep}summary.txt"
    with open(summary_filename, "w", encoding="utf-8") as summary_file:
        summary = get_summary_from_template(filename, chats)
        summary_file.write(summary)

    return True


def get_summary_from_template(filename: str, chats: list):
    """Get the summary from the template"""

    # Get the header template
    total_chats = len(chats)
    header_template = """************************ SUMMMARY ***************************
=============================================================

Filename:       {0:s}
Total Chats:    {1:d}""".format(filename, total_chats)

    # Get the body template
    chats_summary_from_template = get_chats_summary_from_template(
        get_chats_per_sender(chats, False)
    )
    body_template = f"""
+-------+-----------------------------------+--------------+
|  S/N  | By                                | Total Chats  |
+-------+-----------------------------------+--------------+
{chats_summary_from_template}
+-------+-----------------------------------+--------------+
"""

    # Get the footer template
    curr_timestamp = datetime.now().strftime(f"%d-%m-%Y %I:%M %p")
    footer_template = f"Automatically generated by `whatsapp-chat-export-parser` (PHP) on {curr_timestamp}"

    summary_template = f"{header_template}\n{body_template}\n{footer_template}"

    return summary_template


def get_chats_summary_from_template(chats_by_sender: dict) -> str:
    """Get the chats summary from a template"""

    chat_summary_template = "|  {0:^3d}  | {1:32s}  | {2:12d} |"

    def get_chat_summary(sender_no_of_chats, index):
        sender, no_of_chats = sender_no_of_chats

        return chat_summary_template.format(index + 1, sender, no_of_chats)

    chats_summary = list(map(
        get_chat_summary,
        chats_by_sender.items(),
        range(len(chats_by_sender))
    ))

    return "\n".join(chats_summary)
    