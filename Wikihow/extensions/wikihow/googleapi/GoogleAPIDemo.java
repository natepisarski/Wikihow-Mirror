/* Copyright (C) 2002, Google, Inc. */
package com.google.soap.search;
import java.io.*;

/**
 * The GoogleAPIDemo is a demonstration of using the Google Web APIs for
 * search and fetching pages from the cache.
 *
 * @see com.google.soap.search.GoogleSearch
 * @see com.google.soap.search.GoogleSearchResult
 */
public class GoogleAPIDemo {
  /** Demonstration program to call the Google Web APIs service.
   ** Usage:<br>
   **   <tt>java com.google.soap.search.GoogleAPIDemo [key] search Foo</tt><br>
   **   <tt>java com.google.soap.search.GoogleAPIDemo [key] cached http://www.google.com/</tt>
   **   <tt>java com.google.soap.search.GoogleAPIDemo [key] spell "britnay spars"</tt>
   **/
  public static void main(String[] args) {
    // Parse the command line
    if (args.length != 3) {
      printUsageAndExit();
    }
    String clientKey = args[0];
    String directive = args[1];
    String directiveArg = args[2];

    // Report the arguments received
    System.out.println("Parameters:"); 
    System.out.println("Client key = " + clientKey);
    System.out.println("Directive  = " + directive);
    System.out.println("Args       = " + directiveArg);

    // Create a Google Search object, set our authorization key
    GoogleSearch s = new GoogleSearch();
    s.setKey(clientKey);

    // Depending on user input, do search or cache query, then print out result
    try {
      if (directive.equalsIgnoreCase("search")) {
        s.setQueryString(directiveArg);
        GoogleSearchResult r = s.doSearch();
        System.out.println("Google Search Results:");
        System.out.println("======================");
        System.out.println(r.toString());
      } else if (directive.equalsIgnoreCase("cached")) {
        System.out.println("Cached page:");
        System.out.println("============");
        byte [] cachedBytes = s.doGetCachedPage(directiveArg);
        // Note - this conversion to String should be done with reference
        // to the encoding of the cached page, but we don't do that here.
        String cachedString = new String(cachedBytes);
        System.out.println(cachedString);
      } else if (directive.equalsIgnoreCase("spell")) {
        System.out.println("Spelling suggestion:");
        String suggestion = s.doSpellingSuggestion(directiveArg);
        System.out.println(suggestion);
      } else {
        printUsageAndExit();
      }
    } catch (GoogleSearchFault f) {
      System.out.println("The call to the Google Web APIs failed:");
      System.out.println(f.toString());
    }
  }

  private static void printUsageAndExit() {
    System.err.println("Usage: java " + 
                       GoogleAPIDemo.class.getName() +
                       " <client-key>" +
                       " (search <query> | cached <url> | spell <phrase>)");
    System.exit(1);
  }
}
