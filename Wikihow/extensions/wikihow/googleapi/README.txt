Overview
========

This README describes the client Google Web APIs distribution. This
package is all you need to use Google's SOAP-based API. Inside you'll
find a WSDL file which formally describes the API. It can be used with
any language with web services support to call Google. We also provide
a custom Java library that provides a convenience wrapper for Java
programmers. Finally, we have example .NET programs in Visual Basic
and in C# that call the Google Web APIs. For more details, see below.

The Google Web APIs service is in beta release. All interfaces are
subject to change as we refine and extend our APIs. Please see the
terms of use (in the file "LICENSE.txt") for more information.

For questions, comments, etc, please mail Google at
  <api-support@google.com>
You can also discuss Google Web APIs using the Google Group
google.public.web-apis available at
  http://groups.google.com/groups?hl=en&group=google.public.web-apis


Registration and key
--------------------

In order to use Google Web APIs you first must register with Google to
receive an authentication key. You can do this online at
http://www.google.com/apis/.

Your key will have a limit on the number of requests a day that you
can make. The default limit is 1000 queries per day. If you have
problems with your key or getting the correct the daily quota of
queries, plase contact <api-support@google.com>.


How to use the Java API
-----------------------

To quickly try the API, run
  java -cp googleapi.jar com.google.soap.search.GoogleAPIDemo <key> search Foo
Where <key> is your registration key and Foo is the item you wish to
search for. GoogleAPIDemo is a simple demonstration of how to use the
Java API included in googleapi.jar. For usage, run it with no arguments: 
  java -cp googleapi.jar com.google.soap.search.GoogleAPIDemo

GoogleAPIDemo is only a demonstration; Java programmers should look at
the source for GoogleAPIDemo and the included Javadoc for the
GoogleSearch class to learn more about how to use our Java library.

The library has our SOAP endpoint address built in. You may want to
override this endpoint, for instance to point it at a debugging proxy.
You can do this either by calling the appropriate method in
GoogleSearch or by setting the Java property "google.soapEndpointURL".
The default URL is http://api.google.com/search/beta2


How to use the .NET Examples
----------------------------

We have provided example programs that call the Google Web APIs
service from .NET. In the dotnet directory you will find files for
these examples, including

  CSharp Example.exe and VB Example.exe
    Pre-built executables, requires .NET Framework installed on your machine

  CSharp\Form1.cs
    Simple GUI program in C# that calls the API
  CSharp\Google Web APIs Demo.csproj
    Project file for the API demo; open in Visual Studio .NET

  Visual Basic\Google Demo Form.vb
    Simple GUI program in VB that calls the API
  Visual Basic\VB Google Web APIs.vbproj
    Project file for the API demo; open in Visual Studio .NET
    
To browse the code, simply look at the end of the Form code for the
methods at the end that handle clicks on the buttons. The Visual Basic
and C# examples are functionally identical.


How to use the WSDL File
------------------------

The WSDL file provides a standard description of Google's search
services. The file is included with this kit, and is also at
  http://api.google.com/GoogleSearch.wsdl

Many programming languages now understand WSDL and can use this file
to automatically invoke Google's API. For example, the WSDL can be
imported into .NET, converted into Java code using Apache Axis
WSDL2Java, or used directly by Perl SOAP::Lite. The WSDL file has been
tested with SOAP::Lite 0.52, the .NET Framework, (via "Add Web
Reference" or wsdl.exe version 1.0.3705.0), and Apache Axis Beta 1.

Below is a simple Perl script to use the WSDL file to do a query:

use SOAP::Lite;
my $key='000000000000000000000000';
my $query="foo";
my $googleSearch = SOAP::Lite -> service("file:GoogleSearch.wsdl");
my $result = $googleSearch -> doGoogleSearch($key, $query, 0, 10, "false", "", "false", "", "latin1", "latin1");
print "About $result->{'estimatedTotalResultsCount'} results.\n";



Contents of this package:
=========================

googleapi.jar
  Java library for accessing the Google Web APIs service.
GoogleAPIDemo.java
  Example program that uses googleapi.jar.
dotnet/
  Example .NET programs that uses Google Web APIs.

LICENSE
  Terms of use for the API.
APIs_Reference.html
  Reference doc for the API. Describes semantics of all calls and fields.
javadoc/
  Documentation for the example Java libraries.
licenses/
  Licenses for Java code that is redistributed in this package.

GoogleSearch.wsdl
  WSDL description for Google SOAP API.
soap-samples/
  Example SOAP messages and responses.


googleapi.jar contents
======================

com.google.soap.search.*;
  Google's own Java wrapper for the API SOAP calls
JAF 1.0.1 (activation.jar)
  http://java.sun.com/products/javabeans/glasgow/jaf.html
Javamail API (mailapi.jar)
  http://java.sun.com/products/javamail/
Apache SOAP 2.2 (apache-soap-22.jar)
  http://xml.apache.org/soap/
Apache Crimson 1.1.3 (crimson.jar)
  http://xml.apache.org/crimson/

See the "licenses" subdirectory for licensing details of all third
party software. This product includes software developed by the Apache
Software Foundation (http://www.apache.org/).

Release notes
=============

2002-08-30:  Bug fix release for beta2.

  Released a new example client for Visual Basic .NET.

  Updated the C# .NET example client
    Colors fixed, exception handling, GoogleSearch proxy now
    generated via "Add Web Reference" rather than "wsdl.exe".

  Updated WSDL file to correct a bug.
    Visual Studio .NET's "Add Web Reference" feature now supported.

  Added support to Java client for HTTP proxies.
    GoogleSearch has new methods for setting proxy host, port, and
    username and password. Alternately, if the Java system properties
    "http.proxyHost" and "http.proxyPort" are set, those will be used.

  Implemented new mechanism for internationalization and non-ASCII queries.
    Server now correctly handles UTF-8 input and output.

    UTF-8 is now the only supported encoding.
      The APIs server used to handle data in ISO-Latin-1 regardless of
      what encoding was requested. Now it reads and sends data in
      UTF-8. Clients that expect ISO-Latin-1 will need to change to
      handle UTF-8 data instead. This affects clients issuing queries
      in many Western European languages; non-ASCII characters such as
      Eszett (U+00df) or e-grave (U+00e8) are now encoded as two bytes
      of UTF-8, not one byte of ISO-Latin-1. ASCII results are unaffected.

    Query parameters <ie> and <oe> are now ignored; UTF-8 everywhere.

    Deprecated Java client methods setInputEncoding() and setOutputEncoding().

    Updated the APIs_Reference.html documentation. 
      The section on Input and Output Encodings now notes that UTF-8
      is the only supported encoding, and that the <ie> and <oe>
      parameters are ignored.

    Queries in CJK languages (Chinese, Japanese, and Korean) still do
      not work completely correctly. A future release will address
      these problems.


2002-04-11:  Public release of beta2 of the Google Web APIs service.
