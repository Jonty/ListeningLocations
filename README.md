ListeningLocations
==================

A hack for Barcelona Music Hack Day.

ListeningLocations attempts to provide context for your music listening
history, it does this by extracting your Google Latitude location history
(which is automatically tracked by your Android phone), cleaning up the data
somewhat and then finding the music you were listening to in any given location
from your Last.fm profile.

This is then presented as either a location-stream with listening history, or
as charts for each of the places over time.

It was designed to answer a simple question:

    How different is your music listening at home compared to work?

Screenshots
-----------
It's not pretty but then neither am I, and children tend to look like their parents.

The timeline view:
<http://jonty.co.uk/bits/listeninglocations_timeline.png>

The location chart view:
<http://jonty.co.uk/bits/listeninglocations_chart.png>

Notes
-----
* The location grouping will shortly be rewritten to avoid duplicates.
* I have yet to implement Genre charts for locations, this will come shortly.
* Most people do not have Latitude Location history turned on.
* I need to find a frontend developer to make this look less ugly.
