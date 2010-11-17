EdRepo README
=============

For installation instructions, including initially installing and configuring EdRepo and 
possibly themeing it, see the "INSTALL.TXT" file included with EdRepo.  The INSTALL.TXT 
also contains some quick troubleshooting tips and should hopefully be all you need to get 
EdRepo running.

  
  Notes
  -----
  EdRepo has only been tested on x86_64 Mandriva Linux 2010 and 2010.1 (Spring) systems and 
  x86_32 Fedora Linux 13 systems, although it should work on any system capable of running 
  PHP.  Some systems may require some additional configuration for EdRepo to run properly.  
  Some tips on running EdRepo on Mandriva and Fedora Linux systems is included in the 
  INSTALL.TXT file.

  
  What is EdRepo?
  ---------------
EdRepo is a system designed to host collections of open educational resources, although it can 
to some extent be used to host any digitial collection.  

  
  Key Features
  ------------
 - A flexable architecture allowing the use of different storage backend systems depending on the 
    needs of your collection.
 - Multiple user support, with support for privilege levels and access restrictions.
 - Automatic metadata export using the OAI-PMH protocol.
 - Support for a wide range of metadata, suitable for most collections.
 - Few dependencies (the base EdRepo requires only PHP and a webserver, while each storage backend 
    may require additional dependencies.  The defauly MySQL backend requires only PHP and a 
    MySQL server).
 - Themeable, allowing you to make EdRepo look how you want it to.
 - Support for moderating collection contents and users.
 - Designed to be easy to use, easy to configure, and relatively lightweight.
 
  
  Contact
  -------
 You can contact EdRepo's author, Ethan Greer, at:
  elg42@drexel.edu