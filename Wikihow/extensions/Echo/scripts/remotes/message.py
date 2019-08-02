#!/usr/bin/python3

# TODO: This could use https://www.npmjs.com/package/grunt-banana-checker for the check part.

import json
import sys
import collections

def read_i18n_file( file_name ):
    try:
        with open( file_name ) as f:
            return json.load( f, object_pairs_hook=collections.OrderedDict)
    except:
        print( "Failed to decode %s:" % file_name )
        raise

json_object_en = read_i18n_file( 'i18n/en.json' )
json_object_qqq = read_i18n_file( 'i18n/qqq.json' )

try:
    action = sys.argv[1]
except IndexError:
    action = 'add'

def check_messages():
    missing_messages = False
    for key in json_object_en:
        if key not in json_object_qqq:
            print("qqq description missing for key `%s`"% key)
            missing_messages = True

    for key in json_object_qqq:
        if key not in json_object_en:
            print("Key `%s` is present in qqq but has no English translation."% key)
            missing_messages = True

    if missing_messages:
        sys.exit(1)

def add_message():
    key = input("Choose a message key e.g. `mobile-frontend-xxx`:\n")
    if not key:
        print( "A key must be provided" )
        sys.exit(1)
    else:
        if key in json_object_en:
            print("Existing message is:")
            print("\t`%s`\n" % json_object_en[key])
        else:
            print("Creating new message with key `%s`"%key)

        msg = input("What is the message in English?:\n")

        if not msg:
            if key in json_object_en:
                print("Message will remain unchanged.")
            else:
                print("A message for the new key must be provided")
                sys.exit(1)

        if key in json_object_qqq:
            print("Existing message is:")
            print("\t`%s`\n" % json_object_qqq[key])

        prompt = "What is the description for this message (qqq)?\n"
        qqq = input(prompt)
        if not qqq:
            if key in json_object_qqq:
                print("Message will remain unchanged.")
            else:
                print("A message for the qqq code must be provided")
                sys.exit(1)

        if msg:
            json_object_en[key] = msg
        if qqq:
            json_object_qqq[key] = qqq
        save_needed = True

        print("Saving English message...")
        json_file = open( 'i18n/en.json', 'w' )
        json_file.writelines(json.dumps(json_object_en, ensure_ascii=False, indent='\t', separators=(',',': ')))
        json_file.writelines('\n')
        json_file.close()

        print("Saving qqq message...")
        json_file = open( 'i18n/qqq.json', 'w' )
        json_file.writelines(json.dumps(json_object_qqq, ensure_ascii=False, indent='\t', separators=(',',': ')))
        json_file.writelines('\n')
        json_file.close()

        print("Done!")

if action == 'add':
    add_message()
else:
    check_messages()
