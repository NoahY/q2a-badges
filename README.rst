======================
Question2Answer Badges
======================
-----------
Description
-----------
This is a plugin for *Question2Answer* that provides basic badge functionality. 

--------
Features
--------
- currently 22 badges implemented
- badges are categorized into types (e.g. gold, silver, bronze)
- badge requirements modifiable via admin page
- badge notification system triggers jquery notice when awardee accesses the site
- public badge page displays awardable badges
- awarded badges are shown in individual profile pages
- badge system can be deactivated
- full translation table available

------------
Installation
------------
1. Install Question2Answer_
2. Create a new folder in the qa-plugin directory (e.g. badges)
3. Place the files in this repository in that folder.
4. navigate to your site and check your database to make sure the tables were created (^badges and ^userbadges)
5. Go to *Admin -> Plugins* on your q2a install and select the '*Activate badges*' option, then '*Save Changes*'.

.. _Question2Answer: http://www.question2answer.org/install.php

-----------
Translation
-----------
The translation file is *qa-lang-badges.php*.  Copy this file to the *qa-lang/<your-language>/* directory.  Edit the right-hand side strings in this file with notepad++ (don't ever use Window's Notepad. For anything. Ever.), for example, changing:

*'nice_question'=>'Nice Question',*

to

*'good_question'=>'Swali nzuri',*

for Swahili.  Don't edit the string on the left-hand side or bad things will happen.

Once you've completed the translation, don't forget to set the site language in the admin control panel... to Swahili.  

----------
Disclaimer
----------
This is *alpha* software.  It is not intended for production environments unless you are very brave... well, at least a little brave, and maybe a bit foolhardy as well.  Refunds will not be given.  If it breaks, you get to keep both parts.

-------
Release
-------
All code herein is released into the public domain.  Pretend you wrote it.

---------
About q2A
---------
Question2Answer is a free and open source platform for Q&A sites. For more information, visit:

http://www.question2answer.org/

----------
Badge List
----------

========    ==============       ===========
Level[#]    Title                Description
========    ==============       ===========
1           Verified Human       Successfully verified email address

1           Nice Question       Question received +# upvote
2           Good Question       Question received +# upvote
3           Great Question      Question received +# upvote

1           Nice Answer         Answer received +# upvote
2           Good Answer         Answer received +# upvote
3           Great Answer        Answer received +# upvote

1           Voter               Voted # times
2           Avid Voter          Voted # times
3           Devoted Voter       Voted # times

1           Asker               Asked # questions
2           Questioner          Asked # questions
3           Inquisitor          Asked # questions

1           Answerer            Posted # answers
2           Lecturer            Posted # answers
3           Preacher            Posted # answers

1           Commenter           Posted # comments
2           Commentator         Posted # comments
3           Annotator           Posted # comments

1           Learner             Accepted answers to # questions
2           Student             Accepted answers to # questions
3           Scholar             Accepted answers to # questions

1           Watchdog            Flagged # posts as inappropriate
2           Bloodhound          Flagged # posts as inappropriate
3           Pitbull             Flagged # posts as inappropriate

1           Dedicated           Visited every day for # consecutive days
2           Devoted             Visited every day for # consecutive days
3           Zealous             Visited every day for # consecutive days

1           Gifted              # answers selected as best answer
2           Wise                # answers selected as best answer
3           Enlightened         # answers selected as best answer

1           Grateful            selected # answers as best answer
2           Respectful          selected # answers as best answer
3           Reverential         selected # answers as best answer

1           Medalist            Received total of # badges
2           Champion            Received total of # badges
3           Olympian            Received total of # badges

1           Editor              Performed total of # edits
2           Copy Editor         Performed total of # edits
3           Senior Editor       Performed total of # edits
========    =============       ==========================

[#]_ Level refers to difficulty level (e.g. gold, silver, bronze).
