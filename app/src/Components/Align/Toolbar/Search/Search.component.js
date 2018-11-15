import React, {Component} from 'react';
import PropTypes from "prop-types";
import equal from "fast-deep-equal";
import ProjectActions from "../../../../Actions/Project.actions";
import SearchControlsComponent from "./SearchControls/SearchControls.component";

class SearchComponent extends Component {
    static propTypes = {
        job: PropTypes.shape({
            config: PropTypes.shape({
                password: PropTypes.any,
                id: PropTypes.any
            }),
            rows: PropTypes.array,
            rowsDictionary: PropTypes.any
        }),
    };

    constructor(props) {
        super(props);
        this.state = {
            elements: this.getElements(),
            searchResults: [],
            searchResultsDictionary: {},
            occurrencesList: [],
            featuredSearchResult: null,
            active: false,
            fulltext: ''
        };
    }


    componentDidMount() {

    }

    componentWillUnmount() {

    }

    componentDidUpdate(prevProps) {
        if (!equal(this.state.elements, this.getElements(this.props.job.rows))) {
            this.resetSearch();
        }
    }

    render() {
        const {active, featuredSearchResult, occurrencesList} = this.state;
        return (
            <div id="search">
                <form onSubmit={this.onPerformSearch}>
                    <input type="text" value={this.state.fulltext} onChange={this.onSearchChange}/>
                </form>
                {active && <SearchControlsComponent occurrencesList={occurrencesList}
                                                    featuredSearchResult={featuredSearchResult}
                                                    setFeatured={this.setFeatured}/>}
            </div>
        );
    }

    onPerformSearch = (e) => {
        e.preventDefault();
        this.setState({
            featuredSearchResult: this.state.featuredSearchResult++
        })
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
                let searchStrLen = fulltext.length
                let startIndex = 0, index;
                while ((index = item.content.indexOf(fulltext, startIndex)) > -1) {
                    item.occurrences.push({matchPosition: index, searchProgressiveIndex: searchProgressiveIndex});
                    occurrencesList.push({index: item.index, id: item.id});
                    searchProgressiveIndex++;
                    startIndex = index + searchStrLen;
                }
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
        let module = this.state.occurrencesList.length - 1;

        value = this.mod(value,module);

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

        /*else if (value < 0) {
            ProjectActions.emitSearchResults({
                q: this.state.fulltext,
                searchResults: this.state.searchResults,
                searchResultsDictionary: this.state.searchResultsDictionary,
                occurrencesList: this.state.occurrencesList,
                featuredSearchResult: this.state.searchResults.length - 1
            });
            this.setState({
                featuredSearchResult: this.state.searchResults.length - 1
            })
        }
        else {
            ProjectActions.emitSearchResults({
                q: this.state.fulltext,
                searchResults: this.state.searchResults,
                searchResultsDictionary: this.state.searchResultsDictionary,
                occurrencesList: this.state.occurrencesList,
                featuredSearchResult: 0
            });
            this.setState({
                featuredSearchResult: 0
            })
        }*/
    };

    // handling module 
    mod = (n, m) => {
        return ((n % m) + m) % m;
    }
}

export default SearchComponent;
