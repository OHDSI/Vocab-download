#!/usr/bin/perl
use warnings;
use strict;
use DBI;
use POSIX;
use Archive::Zip;
sub table_cols_query { 'SELECT COLUMN_NAME FROM ALL_TAB_COLUMNS WHERE OWNER = ? AND TABLE_NAME = ? ORDER BY COLUMN_ID' }
sub table_cols { my ($db, $p1, $p2) = @_; [ map { $_->[0] } @{ $db->selectall_arrayref(table_cols_query, undef, 'DEV', uc $p2) } ] }
sub csv_line { map { "$_\n" } join ',', map { $_ //= ''; s/[\",]/\\$&/g; $_ } map { @$_ } @_ }
sub all_vocabularies { map { $_->[0] } @{ shift->selectall_arrayref('SELECT * FROM VOCABULARY') } }

sub csv_dump { # (dabatase handler, name of table
	my ($dbh, $table) = @_;
	my $cols = table_cols $dbh, $dbh->{Username}, $table->{name};
	my $sth = $dbh->prepare(join(' ', sprintf('SELECT %s FROM %s t', join(',', @$cols), $table->{name}), $table->{query}));
	$sth->execute(@{$table->{params}});
	my $dump = tmpnam;
	open my $fh, '>', $dump;
	print $fh csv_line $cols;
	while (my $line = $sth->fetch) {
		print $fh csv_line $line;
	}
	close $fh;
	return $dump;
}

do { print <<END
	Usage:
	./dump.pl \<user/pass\@host/sid\> \<output.zip\> [vocabulary ids]
	'vocabulary ids' is a comma-separated list of required vocabularies, omit the list in order to dump all available
END
} and exit unless @ARGV;

die "Valid database access information required." unless shift =~ /^(.+)\/(.+)\@(.+)\/(.+)$/;
my $dbh = DBI->connect(sprintf('dbi:Oracle:host=%s;sid=%s', $3, $4), $1, $2) or die "Valid database access information required.";
$dbh->do('ALTER SESSION SET NLS_DATE_FORMAT="YYYYMMDD"');
$dbh->do('ALTER SESSION SET CURRENT_SCHEMA = DEV');
my $output = shift or die "File name for output file required.";
my @vocabularies = split /,/, (shift or join ',', all_vocabularies $dbh);
my $placeholder = join ', ', split //, '?' x @vocabularies;
my $zip = new Archive::Zip;
warn "Writing vocabularies ".join(',', @vocabularies)." to $output.";

$zip->addFile(csv_dump($dbh, $_), sprintf('%s.csv', $_->{name})) for
{
	name => 'CONCEPT',
	query => sprintf('WHERE VOCABULARY_ID IN (%s)', $placeholder),
	params => [ @vocabularies ],
},
{
	name => 'CONCEPT_RELATIONSHIP',
	query => sprintf('WHERE EXISTS (SELECT * FROM CONCEPT WHERE CONCEPT_ID_1 = CONCEPT_ID AND VOCABULARY_ID IN (%s)) AND EXISTS (SELECT * FROM CONCEPT WHERE CONCEPT_ID_2 = CONCEPT_ID AND VOCABULARY_ID IN (%s))', $placeholder, $placeholder),
	params => [ @vocabularies, @vocabularies ],
},
{
	name => 'SOURCE_TO_CONCEPT_MAP',
	query => sprintf('WHERE SOURCE_VOCABULARY_ID IN (%s)', $placeholder),
	params => [ @vocabularies ],
},
{
	name => 'CONCEPT_ANCESTOR',
	query => sprintf('WHERE EXISTS (SELECT * FROM CONCEPT WHERE ANCESTOR_CONCEPT_ID = CONCEPT_ID AND VOCABULARY_ID IN (%s)) AND EXISTS (SELECT * FROM CONCEPT WHERE DESCENDANT_CONCEPT_ID = CONCEPT_ID AND VOCABULARY_ID IN (%s))', $placeholder, $placeholder),
	params => [ @vocabularies, @vocabularies ],	
},
{
	name => 'CONCEPT_SYNONYM',
	query => sprintf('WHERE EXISTS (SELECT * FROM CONCEPT c WHERE t.CONCEPT_ID = c.CONCEPT_ID AND VOCABULARY_ID IN (%s))', $placeholder),
	params => [ @vocabularies ],	
},
{
	name => 'VOCABULARY',
	query => sprintf('WHERE VOCABULARY_ID IN (%s)', $placeholder),
	params => [ @vocabularies ],
},
{
	name => 'RELATIONSHIP',
	query => '',
	params => [],
};
die "Cannot write zip file: $!" unless $zip->writeToFileNamed($output) == Archive::Zip::AZ_OK;
unlink $_->{externalFileName} for $zip->members;