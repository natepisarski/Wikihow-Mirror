from sys import argv
with open(argv[1], 'r') as f:
    s = f.read()
print 'TRUNCATE `knowledgebox_articles`;\n' \
      'INSERT INTO `knowledgebox_articles` ' \
      '(`kba_aid`,`kba_topic`,`kba_phrase`) VALUES' \
      + ', '.join('("%s","%s","%s")' % (id, noun, phrase)
                  for _, id, _, _, noun, phrase
                  in [l.split(',') for l in s.split('\n')[1:] if l]) \
      + ';'