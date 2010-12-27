Lexentity: A context-aware, medium-neutral entity maker
=======================================================
by Sean Coates

    Let's face it--this sentence is much "uglier" than the one below it.
    Let’s face it–this sentence is much “prettier” than the one above it.

Lexentity is a simple piece of software that takes HTML as input and outputs
a context-aware, medium-neutral representation of that HTML, with apostrophes,
quotes, emdashes, ellipses, accents, etc., replaced with their respective
numeric XML/Unicode entities.

Context-aware
-------------

Context is important. It is especially important when considering a piece of
HTML like this:

    <p>…and here's the example code:</p>
    <pre><code>echo "watermelon!\n";</pre></code>

Contextually, you'd want <code>here's</code> to become <code>here’s</code>, but
you certainly don't want the code to read <code>echo “watermelon!\n”;</code>.

A fancy/smart/curly quotes apostrophe is appropriate, but curly quotes in the
code are likely to cause a parse error.

Lexentity understands its context, and acts appropriately, my means of lexical
analysis, and turning tokens into text, not through a mostly-naive and
overly-complicated regular expression.

Medium-neutral
--------------

My friend and colleague Jon Gibbins said it best in
[http://dotjay.co.uk/2006/sep/named-html-entities-in-rss](this piece on his blog).
In modern systems, you can't count on your HTML to always be represented as
HTML. It's often (poorly) embedded in RSS or other HTML-like media, as XML.

Therefore, it is important to avoid HTML-specific entities like
<code>&rdquo;</code> and <code>&hellip;</code>, and instead use their Unicode
code point to form numeric entities such as <code>&amp;#8230;</code>. This ensures
proper display on any terminal that can properly render Unicode XML, and avoids
missing entity errors.

Demo
----

Try a demo at
[http://files.seancoates.com/lexentity/](http://files.seancoates.com/lexentity).

