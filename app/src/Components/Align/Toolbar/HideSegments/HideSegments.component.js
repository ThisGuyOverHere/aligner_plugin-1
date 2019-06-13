import React, {Component} from 'react';
import PropTypes from "prop-types";

class HideSegments extends Component {
    static propTypes = {
        job: PropTypes.shape({
            counters: PropTypes.shape({
                hideIndexesMap: PropTypes.array,
                missAlignmentsIndexesMap: PropTypes.array
            }),
        }),
        close: PropTypes.func
    };

    constructor(props) {
        super(props);
        this.state = {
            active: false,
            fulltext: ''
        };
    }

    componentDidUpdate(prevProps) {

    }

    componentDidMount() {
      
    }

    componentWillUnmount() {
        /*  setTimeout(() => {
              ProjectActions.emitSearchResults({
                  q: '',
                  searchResults: [],
                  searchResultsDictionary: {},
                  occurrencesList: [],
                  featuredSearchResult: 0
              });
          }, 0)*/
    }

    render() {
        const {job: {counters: {hideIndexesMap}}} = this.props;
        return (
            <div id="search">
                <div> {hideIndexesMap.length} </div>
            </div>
        );
    }

    /*handlerSearch = (keyName, e, handle) => {
        e.preventDefault();
        this.searchInput.focus();
    };

    onPerformSearch = (e) => {
        e.preventDefault();
        this.setFeatured(this.state.featuredSearchResult + 1);
    };

    getElements = () => {
        let elements = [];
        if (this.props.job.rows) {
            this.props.job.rows.map((row, index) => {
                const source = {
                    content: row.source.content_clean ? row.source.content_clean.toLowerCase() : '',
                    type: 'source',
                    id: row.source.id,
                    index: index
                };
                const target = {
                    content: row.target.content_clean ? row.target.content_clean.toLowerCase() : '',
                    type: 'target',
                    id: row.target.id,
                    index: index
                };
                elements.push(source, target);
            });
        }
        return elements;
    };
    resetSearch = () => {
        if (this.state.active) {
            setTimeout(() => {
                ProjectActions.emitSearchResults({
                    q: '',
                    searchResults: [],
                    searchResultsDictionary: {},
                    occurrencesList: [],
                    featuredSearchResult: 0
                });
            }, 0)
        }
        this.setState({
            elements: this.getElements(this.props.job.rows),
            active: false,
            searchResults: [],
            searchResultsDictionary: {},
            featuredSearchResult: 0,
            occurrencesList: [],
            fulltext: ''
        });
    };
    onSearchChange = (event) => {
        let fulltext = event.target.value.toLowerCase();
        let searchResultsDictionary = {};
        const elements = JSON.parse(JSON.stringify(this.state.elements));
        let active = false;
        let searchProgressiveIndex = 0;
        let searchResults = [];
        let occurrencesList = [];

        if (fulltext.length > 0) {
            active = true;
            searchResults = elements.filter(function (item) {
                return item.content.indexOf(fulltext) !== -1;
            }).map(item => {
                item.occurrences = [];
                let searchStrLen = fulltext.length;
                let startIndex = 0, index;
                while ((index = item.content.indexOf(fulltext, startIndex)) > -1) {
                    item.occurrences.push({matchPosition: index, searchProgressiveIndex: searchProgressiveIndex});
                    occurrencesList.push({index: item.index, id: item.id});
                    searchProgressiveIndex++;
                    startIndex = index + searchStrLen;
                }
                item.occurrences.reverse();
                searchResultsDictionary[item.id] = item;
                return item
            });
            ProjectActions.emitSearchResults({
                q: fulltext,
                searchResults: searchResults,
                searchResultsDictionary: searchResultsDictionary,
                occurrencesList: occurrencesList,
                featuredSearchResult: 0
            });
            this.setState({
                fulltext: event.target.value,
                active: active,
                searchResults: searchResults,
                searchResultsDictionary: searchResultsDictionary,
                occurrencesList: occurrencesList,
                featuredSearchResult: 0
            })
        } else {
            this.resetSearch()
        }


    };

    setFeatured = (value) => {
        if (this.state.occurrencesList.length > 1) {
            let module = this.state.occurrencesList.length;
            value = this.mod(value, module);
        } else {
            value = 0;
        }

        ProjectActions.emitSearchResults({
            q: this.state.fulltext,
            searchResults: this.state.searchResults,
            searchResultsDictionary: this.state.searchResultsDictionary,
            occurrencesList: this.state.occurrencesList,
            featuredSearchResult: value
        });
        this.setState({
            featuredSearchResult: value
        })
    };

    // handling module
    mod = (n, m) => {
        return ((n % m) + m) % m;
    }*/
}

export default HideSegments;
