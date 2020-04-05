#!/usr/bin/python

import sys, getopt

def convertHtmlToPhpString(inputFile, stringName):
	output = ""
	with open (inputFile) as fp:
		line = fp.readline()
		cnt = 1
		while line:
			#print("$%s .= \'%s\'.NL;" % (stringName, line.rstrip()))
			output += str("$%s .= \'%s\'.NL;\n" % (stringName, line.rstrip()))
			line = fp.readline()
			cnt += 1
	return output

def writeStringToPhpFile(string, outputFile):
	fp = open(outputFile, "w+")
	fp.write(string)
	fp.close()

def printHelp():
	print('---------------------------------------------------------------------------------------------')
	print('------------------------------ Help menu ----------------------------------------------------')
	print('Correct usage of the python script is as following:\n')
	print('py ' + __file__ + ' -i <inputfile> -o <outputfile> -s <stringname>\n')
	print('    -i --ifile  <inputfile>  the path and name of the input file including the extension')
	print('                             example: path/to/webpage.html\n')
	print('    -o --ofile  <outputfile> the path and name of the output php file including the extension')
	print('                             example: path/to/webpage.php\n')
	print('    -s --string <stringname> the name of the string that will be used as php string parameter')
	print('                             example: HtmlPage\n')
	print('    -h                       prints the help menu\n')
	print('Exampe: py ' + __file__ + ' -i webpage.html -o output.php -s HTMLBody\n')
	print('------------------------------ END menu -----------------------------------------------------')
	print('---------------------------------------------------------------------------------------------')
	
def main(argv):
	inputFile = ''
	outputFile = ''
	stringName = ''
	phpContent = ''
	
	try:
		opts, args = getopt.getopt(argv,"hi:o:s:",["ifile=","ofile=","string"])
	except getopt.GetoptError:
		print('Whoops, an error occured\n')
		printHelp()
		sys.exit(2)
	for opt, arg in opts:
		if opt == '-h':
			printHelp()
			sys.exit()
		elif opt in ("-i", "--ifile"):
			inputFile = arg
		elif opt in ("-o", "--ofile"):
			outputFile = arg
		elif opt in ("-s", "--string"):
			stringName = arg
			
	print( 'Input file is: ', inputFile)
	print( 'Output file is: ', outputFile)
	print( 'String name is: ', stringName)
	
	phpContent = convertHtmlToPhpString(inputFile, stringName)
	writeStringToPhpFile(phpContent, outputFile)
	
	print('\nSuccessfully converted the contents of %s to %s where the input file is used as a string named to the given stringname %s.' % (inputFile, outputFile, stringName))
			

if __name__ == "__main__":
	main(sys.argv[1:])
	
