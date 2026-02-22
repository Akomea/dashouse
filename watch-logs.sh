#!/bin/bash
# Watch PHP error logs in real-time
echo "=== Watching PHP Error Logs ==="
echo "Press Ctrl+C to stop"
echo ""

# Create log file if it doesn't exist
touch "/Users/das house website/php-errors.log"

# Watch the error log
tail -f "/Users/das house website/php-errors.log" 2>/dev/null &
TAIL_PID=$!

# Also watch the server log if it exists
if [ -f "/Users/das house website/php-server.log" ]; then
    tail -f "/Users/das house website/php-server.log" &
    TAIL_PID2=$!
fi

# Wait for user to press Ctrl+C
trap "kill $TAIL_PID $TAIL_PID2 2>/dev/null; exit" INT
wait

