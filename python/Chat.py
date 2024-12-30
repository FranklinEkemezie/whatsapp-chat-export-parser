from datetime import datetime

class Chat:
    """Represents a Chat"""

    def __init__(self, sender: str, message: str, timestamp: datetime):
        self.sender     = sender
        self.message    = message
        self.timestamp  = timestamp

    def __dict__(self):
        return {
            'sender':   self.sender,
            'message':  self.message,
            'timestamp':self.timestamp.strftime(f"%d-%m-%Y %I:%M %p")
        }
    
    def sent_after(self, target_date: datetime) -> bool:
        return self.timestamp > target_date
    
    def sent_before(self, target_date: datetime) -> bool:
        return self.timestamp < target_date
    
