from sys import argv
with open(argv[1], 'r') as f:
    s = f.read()
print 'array(' + \
      ','.join('\n    %s => array("%s", "%s")' % (id, noun, phrase)
               for _, id, _, _, noun, phrase
               in [l.split(',') for l in s.split('\n')[1:] if l]) \
      + '\n);'
